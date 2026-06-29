<?php

namespace Tests\Feature\Actions\Reports;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\LlmInteraction;
use App\DTOs\TranscribeResult;
use App\Services\SpeechToTextService;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiResponseException;
use App\Exceptions\AiUnavailableException;

class TranscribeReportTest extends TestCase
{
    use RefreshDatabase;

    // ---- AUTH HELPERS (mirror ExtractReportDataTest) --------

    private function mockJwtForUserId(int $id): void
    {
        $token = new class($id) {
            private $id;
            public function __construct($id) { $this->id = $id; }
            public function claims() {
                $id = $this->id;
                return new class($id) {
                    private $id;
                    public function __construct($id) { $this->id = $id; }
                    public function get($key) { return $key === 'sub' ? $this->id : null; }
                };
            }
        };

        $jwtMock = $this->createMock(\App\Services\JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(\App\Services\JwtService::class, $jwtMock);
    }

    private function grantPermission(User $user, string $slug): void
    {
        $perm = \App\Models\Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
    }

    private function actingWithPermission(string $slug): User
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, $slug);
        return $user;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer token'];
    }

    // ---- TEST DATA HELPERS ---------------------------------

    private function createTestReport(): PatientReport
    {
        $patient = Patient::factory()->create();
        return PatientReport::factory()->create([
            'patient_id' => $patient->id,
        ]);
    }

    private function validAudio(): UploadedFile
    {
        return UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');
    }

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'audio' => $this->validAudio(),
            'diarization' => '1',
            'language' => 'es',
        ], $overrides);
    }

    // ---- STT MOCK HELPERS ----------------------------------

    private function mockSttSuccess(): void
    {
        $this->mock(SpeechToTextService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn(new TranscribeResult(
                    transcript: 'Transcripción completa de la consulta medica.',
                    segments: [
                        ['speaker' => 'Speaker 1', 'text' => 'Buenos dias, en que puedo ayudarle?', 'start' => 0.0, 'end' => 3.5],
                        ['speaker' => 'Speaker 2', 'text' => 'Doctor, tengo dolor de cabeza.', 'start' => 4.0, 'end' => 8.2],
                        ['speaker' => 'Speaker 1', 'text' => 'Vamos a revisar. Ha tenido fiebre?', 'start' => 8.5, 'end' => 10.0],
                        ['speaker' => 'Speaker 2', 'text' => 'No, solo dolor de cabeza constante.', 'start' => 10.5, 'end' => 12.0],
                    ],
                    language: 'es',
                    durationSeconds: 15.3,
                    processingTimeMs: 2500,
                ));
        });
    }

    private function mockSttNoDiarization(): void
    {
        $this->mock(SpeechToTextService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn(new TranscribeResult(
                    transcript: 'Transcripción sin identificacion de hablantes.',
                    segments: [
                        ['speaker' => 'Speaker 1', 'text' => 'Texto completo sin identificar hablantes.', 'start' => 0.0, 'end' => 10.0],
                    ],
                    language: 'es',
                    durationSeconds: 10.0,
                    processingTimeMs: 2000,
                ));
        });
    }

    private function mockSttTimeout(): void
    {
        $this->mock(SpeechToTextService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andThrow(new AiTimeoutException('STT request timed out'));
        });
    }

    private function mockSttMalformedJson(): void
    {
        $this->mock(SpeechToTextService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andThrow(new AiResponseException('Invalid JSON response from STT'));
        });
    }

    private function mockSttUnavailable(): void
    {
        $this->mock(SpeechToTextService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andThrow(new AiUnavailableException('STT service unavailable'));
        });
    }

    // ---- TEST 1: HAPPY PATH WITH DIARIZATION ---------------

    public function test_transcribe_audio_returns_200_with_transcription_data(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttSuccess();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'transcript',
                'segments',
                'language',
                'duration_seconds',
            ],
            'meta',
            'message',
        ]);
        $response->assertJsonFragment([
            'message' => 'Transcripción completada',
        ]);
        $response->assertJsonFragment([
            'language' => 'es',
        ]);
        $this->assertCount(4, $response->json('data.segments'));
        $this->assertEquals('Médico', $response->json('data.segments.0.speaker'));
        $this->assertEquals('Paciente', $response->json('data.segments.1.speaker'));
    }

    // ---- TEST 2: LLM INTERACTION PERSISTENCE ---------------

    public function test_transcribe_audio_persists_llm_interaction(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttSuccess();

        $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $this->assertDatabaseHas('llm_interactions', [
            'patient_report_id' => $report->id,
            'type' => LlmInteraction::TYPE_STT,
        ]);
    }

    // ---- TEST 3: NO DIARIZATION (SINGLE SPEAKER) -----------

    public function test_transcribe_audio_with_diarization_disabled_returns_single_speaker(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttNoDiarization();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(['diarization' => '0']),
            $this->authHeader()
        );

        $response->assertStatus(200);
        $segments = $response->json('data.segments');
        $this->assertCount(1, $segments);
        $this->assertEquals('Speaker 1', $segments[0]['speaker']);
    }

    // ---- TEST 4: NO AUTHENTICATION -------------------------

    public function test_transcribe_audio_without_jwt_returns_401(): void
    {
        $report = $this->createTestReport();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData()
        );

        $response->assertStatus(401);
    }

    // ---- TEST 5: NO PERMISSION -----------------------------

    public function test_transcribe_audio_without_permission_returns_403(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $report = $this->createTestReport();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(403);
    }

    // ---- TEST 6: MISSING AUDIO FILE ------------------------

    public function test_transcribe_audio_without_audio_file_returns_422(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            [
                'diarization' => 'true',
                'language' => 'es',
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
    }

    // ---- TEST 7: UNSUPPORTED FORMAT ------------------------

    public function test_transcribe_audio_with_unsupported_format_returns_415(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData([
                'audio' => UploadedFile::fake()->create('audio.txt', 1024, 'text/plain'),
            ]),
            $this->authHeader()
        );

        $response->assertStatus(415);
    }

    // ---- TEST 8: FILE TOO LARGE ----------------------------

    public function test_transcribe_audio_with_file_too_large_returns_413(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData([
                'audio' => UploadedFile::fake()->create('audio.wav', 30000, 'audio/wav'),
            ]),
            $this->authHeader()
        );

        $response->assertStatus(413);
    }

    // ---- TEST 9: REPORT NOT FOUND --------------------------

    public function test_transcribe_audio_report_not_found_returns_404(): void
    {
        $user = $this->actingWithPermission('report.edit');
        // Mock STT to prevent container resolution failure
        $this->mock(SpeechToTextService::class);

        $response = $this->post(
            '/reports/99999/transcribe',
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'Informe no encontrado',
        ]);
    }

    // ---- TEST 10: STT TIMEOUT ------------------------------

    public function test_transcribe_audio_stt_timeout_returns_500(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttTimeout();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al procesar el audio',
        ]);
    }

    // ---- TEST 11: STT MALFORMED JSON -----------------------

    public function test_transcribe_audio_stt_malformed_json_returns_500(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttMalformedJson();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al procesar el audio',
        ]);
    }

    // ---- TEST 12: STT UNAVAILABLE --------------------------

    public function test_transcribe_audio_stt_unavailable_returns_503(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $report = $this->createTestReport();
        $this->mockSttUnavailable();

        $response = $this->post(
            "/reports/{$report->id}/transcribe",
            $this->formData(),
            $this->authHeader()
        );

        $response->assertStatus(503);
        $response->assertJsonFragment([
            'message' => 'Servicio de transcripción temporalmente no disponible',
        ]);
    }
}
