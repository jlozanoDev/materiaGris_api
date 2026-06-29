<?php

namespace App\Commands\Reports;

use App\DTOs\TranscribeResult;
use App\Exceptions\AiResponseException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiUnavailableException;
use App\Exceptions\PermissionDeniedException;
use App\Models\LlmInteraction;
use App\Models\User;
use App\Repositories\Report\PatientReportReadRepository;
use App\Services\PermissionService;
use App\Services\SpeakerClassifierService;
use App\Services\SpeechToTextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;

class TranscribeReportCommand
{
    public function __construct(
        private readonly PatientReportReadRepository $reportRepository,
        private readonly SpeechToTextService $sttService,
        private readonly PermissionService $permissionService,
        private readonly SpeakerClassifierService $classifierService,
    ) {}

    /**
     * Execute the transcribe report use case.
     *
     * @param int              $reportId    The patient report ID
     * @param UploadedFile     $audio       The uploaded audio file
     * @param bool             $diarization Whether to enable speaker diarization
     * @param string|null      $language    Optional ISO 639-1 language code
     * @param User             $user        The authenticated user
     * @return TranscribeResult
     *
     * @throws PermissionDeniedException
     * @throws ModelNotFoundException
     * @throws \App\Exceptions\AiTimeoutException
     * @throws \App\Exceptions\AiResponseException
     * @throws \App\Exceptions\AiUnavailableException
     */
    public function execute(
        int $reportId,
        UploadedFile $audio,
        bool $diarization,
        ?string $language,
        User $user,
    ): TranscribeResult {
        // 1. Permission check
        $this->permissionService->ensure($user, 'report.edit');

        // 2. Report lookup
        $report = $this->reportRepository->buscarPorId($reportId);

        if (! $report) {
            throw new ModelNotFoundException('Informe no encontrado');
        }

        // 3. Audio processing — PII/PHI safe: encode to base64 in memory, never stored
        $audioContent = $audio->get();
        $mimeType = $audio->getMimeType();

        // MiMo only accepts mp3, flac, m4a, wav, ogg — convert webm from browser MediaRecorder
        if ($mimeType === 'audio/webm' || $mimeType === 'video/webm') {
            $audioContent = $this->convertToMp3($audioContent);
            $mimeType = 'audio/mpeg';
        }

        $audioBase64 = base64_encode($audioContent);
        $audioSizeBytes = strlen($audioContent);

        // 4. STT call with persistence on success AND failure
        try {
            $result = $this->sttService->transcribe($audioBase64, $mimeType, $diarization, $language);

            // Classify speakers if diarization is enabled
            if ($diarization && count($result->segments) > 0) {
                $classifiedSegments = $this->classifierService->classify($result->segments);
                $result = new TranscribeResult(
                    transcript: $result->transcript,
                    segments: $classifiedSegments,
                    language: $result->language,
                    durationSeconds: $result->durationSeconds,
                    processingTimeMs: $result->processingTimeMs,
                );
            }

            // Persist success with metadata only (PII/PHI safe)
            LlmInteraction::create([
                'patient_report_id' => $reportId,
                'type' => LlmInteraction::TYPE_STT,
                'request_payload' => [
                    'audio_size_bytes' => $audioSizeBytes,
                    'audio_format' => $mimeType,
                    'diarization' => $diarization,
                    'language' => $language,
                    'model' => config('stt.model', 'mimo-v2.5'),
                    'provider' => config('stt.provider', 'opencode'),
                ],
                'response_payload' => [
                    'transcript' => $result->transcript,
                    'segments' => $result->segments,
                    'duration_seconds' => $result->durationSeconds,
                    'language' => $result->language,
                    'segments_count' => count($result->segments),
                    'processing_time_ms' => $result->processingTimeMs,
                    'diarization_applied' => $diarization,
                ],
                'processing_time_ms' => $result->processingTimeMs,
            ]);
            
            return $result;
        } catch (AiTimeoutException | AiResponseException | AiUnavailableException $e) {
            // Persist failure with metadata + error info
            LlmInteraction::create([
                'patient_report_id' => $reportId,
                'type' => LlmInteraction::TYPE_STT,
                'request_payload' => [
                    'audio_size_bytes' => $audioSizeBytes,
                    'audio_format' => $mimeType,
                    'diarization' => $diarization,
                    'language' => $language,
                    'model' => config('stt.model', 'mimo-v2.5'),
                    'provider' => config('stt.provider', 'opencode'),
                ],
                'response_payload' => [
                    'error' => get_class($e),
                    'error_message' => $e->getMessage(),
                ],
                'processing_time_ms' => null,
            ]);

            throw $e;
        }
    }

    /**
     * Convert audio to mp3 using FFmpeg (pipe-based, no disk I/O).
     *
     * @throws AiResponseException if conversion fails
     */
    private function convertToMp3(string $audioContent): string
    {
        // Verify FFmpeg is available
        $ffmpegPath = trim(shell_exec('command -v ffmpeg 2>/dev/null') ?? '');

        if (empty($ffmpegPath)) {
            throw new AiResponseException('FFmpeg not available for audio conversion');
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [$ffmpegPath, '-i', 'pipe:0', '-f', 'mp3', '-b:a', '64k', '-loglevel', 'error', 'pipe:1'],
            $descriptors,
            $pipes,
        );

        if (! is_resource($process)) {
            throw new AiResponseException('Failed to start FFmpeg for audio conversion');
        }

        fwrite($pipes[0], $audioContent);
        fclose($pipes[0]);

        $converted = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || empty($converted)) {
            throw new AiResponseException('FFmpeg audio conversion failed: ' . ($stderr ?: 'unknown error'));
        }

        return $converted;
    }
}
