<?php

namespace Tests\Unit\Commands\Reports;

use App\Commands\Reports\TranscribeReportCommand;
use App\DTOs\TranscribeResult;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\PermissionDeniedException;
use App\Models\LlmInteraction;
use App\Models\PatientReport;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\Report\PatientReportReadRepository;
use App\Services\PermissionService;
use App\Services\SpeakerClassifierService;
use App\Services\SpeechToTextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscribeReportCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createValidTranscribeResult(): TranscribeResult
    {
        return new TranscribeResult(
            transcript: 'Esta es una transcripción de prueba',
            segments: [
                ['speaker' => 'Speaker 1', 'text' => 'Hola', 'start' => 0.0, 'end' => 1.0],
            ],
            language: 'es',
            durationSeconds: 10.5,
            processingTimeMs: 500,
        );
    }

    #[Test]
    public function test_execute_calls_stt_service_with_correct_params(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $audio = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->once())
            ->method('transcribe')
            ->with(
                $this->callback(fn ($base64) => is_string($base64) && base64_decode($base64, true) !== false),
                'audio/wav',
                true,
                null,
            )
            ->willReturn($this->createValidTranscribeResult());

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with($report->id)
            ->willReturn($report);

        $classifier = $this->createMock(SpeakerClassifierService::class);
        $classifier->expects($this->once())
            ->method('classify')
            ->willReturnCallback(function (array $segments): array {
                return array_map(function (array $seg): array {
                    $seg['speaker'] = $seg['speaker'] === 'Speaker 1' ? 'Médico' : 'Paciente';
                    return $seg;
                }, $segments);
            });

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        $result = $command->execute($report->id, $audio, true, null, $user);

        $this->assertInstanceOf(TranscribeResult::class, $result);
        $this->assertEquals('Esta es una transcripción de prueba', $result->transcript);
    }

    #[Test]
    public function test_execute_persists_llm_interaction_with_stt_type(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $audio = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->once())
            ->method('transcribe')
            ->willReturn($this->createValidTranscribeResult());

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->willReturn($report);

        $classifier = $this->createMock(SpeakerClassifierService::class);
        $classifier->expects($this->once())
            ->method('classify')
            ->willReturnCallback(function (array $segments): array {
                return array_map(function (array $seg): array {
                    $seg['speaker'] = $seg['speaker'] === 'Speaker 1' ? 'Médico' : 'Paciente';
                    return $seg;
                }, $segments);
            });

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        $command->execute($report->id, $audio, true, null, $user);

        $this->assertDatabaseHas('llm_interactions', [
            'patient_report_id' => $report->id,
            'type' => LlmInteraction::TYPE_STT,
        ]);
    }

    #[Test]
    public function test_execute_checks_report_edit_permission(): void
    {
        $user = User::factory()->make(['id' => 2]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit')
            ->willThrowException(new PermissionDeniedException('User lacks required permission: report.edit'));

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->never())->method('buscarPorId');

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->never())->method('transcribe');

        $classifier = $this->createMock(SpeakerClassifierService::class);

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        $this->expectException(PermissionDeniedException::class);

        $command->execute(1, UploadedFile::fake()->create('audio.wav'), true, null, $user);
    }

    #[Test]
    public function test_execute_throws_when_report_not_found(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with(999)
            ->willReturn(null);

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->never())->method('transcribe');

        $classifier = $this->createMock(SpeakerClassifierService::class);

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        $this->expectException(ModelNotFoundException::class);

        $command->execute(999, UploadedFile::fake()->create('audio.wav'), true, null, $user);
    }

    #[Test]
    public function test_execute_llm_interaction_request_payload_contains_metadata_only(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $audio = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');

        $transcribeResult = new TranscribeResult(
            transcript: 'Test transcript',
            segments: [['speaker' => 'Speaker 1', 'text' => 'Test', 'start' => 0.0, 'end' => 1.0]],
            language: 'es',
            durationSeconds: 5.0,
            processingTimeMs: 300,
        );

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->once())
            ->method('transcribe')
            ->willReturn($transcribeResult);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->willReturn($report);

        $classifier = $this->createMock(SpeakerClassifierService::class);
        $classifier->expects($this->once())
            ->method('classify')
            ->willReturnCallback(function (array $segments): array {
                return array_map(function (array $seg): array {
                    $seg['speaker'] = $seg['speaker'] === 'Speaker 1' ? 'Médico' : 'Paciente';
                    return $seg;
                }, $segments);
            });

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        $command->execute($report->id, $audio, true, null, $user);

        $interaction = LlmInteraction::where('patient_report_id', $report->id)->first();
        $this->assertNotNull($interaction);

        $requestPayload = $interaction->request_payload;

        // Verify request_payload has metadata keys and NOT audio data
        $this->assertArrayHasKey('audio_size_bytes', $requestPayload);
        $this->assertArrayHasKey('audio_format', $requestPayload);
        $this->assertArrayHasKey('diarization', $requestPayload);
        $this->assertArrayHasKey('language', $requestPayload);
        $this->assertArrayHasKey('model', $requestPayload);
        $this->assertArrayHasKey('provider', $requestPayload);

        // Verify NO audio content or transcript text
        $this->assertArrayNotHasKey('audio_content', $requestPayload);
        $this->assertArrayNotHasKey('audio_base64', $requestPayload);

        // Verify response_payload has metadata only (no transcript text)
        $responsePayload = $interaction->response_payload;
        $this->assertArrayHasKey('duration_seconds', $responsePayload);
        $this->assertArrayHasKey('language', $responsePayload);
        $this->assertArrayHasKey('segments_count', $responsePayload);
        $this->assertArrayHasKey('processing_time_ms', $responsePayload);
        $this->assertArrayHasKey('diarization_applied', $responsePayload);

        $this->assertArrayHasKey('transcript', $responsePayload);
        $this->assertArrayHasKey('segments', $responsePayload);
    }

    #[Test]
    public function test_execute_persists_llm_interaction_on_stt_failure(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $audio = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');

        $sttService = $this->createMock(SpeechToTextService::class);
        $sttService->expects($this->once())
            ->method('transcribe')
            ->willThrowException(new AiTimeoutException('STT request timed out after 120 seconds'));

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->willReturn($report);

        $classifier = $this->createMock(SpeakerClassifierService::class);

        $command = new TranscribeReportCommand(
            $reportRepo,
            $sttService,
            $permissionService,
            $classifier,
        );

        try {
            $command->execute($report->id, $audio, true, null, $user);
            $this->fail('Expected AiTimeoutException was not thrown');
        } catch (AiTimeoutException $e) {
            $this->assertEquals('STT request timed out after 120 seconds', $e->getMessage());
        }

        // Verify LlmInteraction persisted even on failure
        $this->assertDatabaseHas('llm_interactions', [
            'patient_report_id' => $report->id,
            'type' => LlmInteraction::TYPE_STT,
        ]);

        $interaction = LlmInteraction::where('patient_report_id', $report->id)->first();
        $this->assertNotNull($interaction);

        // Request payload should have metadata (no audio content)
        $requestPayload = $interaction->request_payload;
        $this->assertArrayHasKey('audio_size_bytes', $requestPayload);
        $this->assertArrayHasKey('audio_format', $requestPayload);
        $this->assertArrayNotHasKey('audio_content', $requestPayload);

        // Response payload should have error info
        $responsePayload = $interaction->response_payload;
        $this->assertArrayHasKey('error', $responsePayload);
        $this->assertArrayHasKey('error_message', $responsePayload);
        $this->assertEquals('STT request timed out after 120 seconds', $responsePayload['error_message']);
    }
}
