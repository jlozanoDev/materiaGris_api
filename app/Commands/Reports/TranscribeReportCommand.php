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
        $audioBase64 = base64_encode($audio->get());
        $mimeType = $audio->getMimeType();
        $audioSizeBytes = $audio->getSize();

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
}
