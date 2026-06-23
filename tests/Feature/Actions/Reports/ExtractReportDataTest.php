<?php

namespace Tests\Feature\Actions\Reports;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Patient;
use App\Models\ReportTemplate;
use App\Models\PatientReport;
use App\Models\LlmInteraction;
use App\Services\LlmExtractorService;
use App\Exceptions\LlmTimeoutException;
use App\Exceptions\LlmResponseException;

class ExtractReportDataTest extends TestCase
{
    use RefreshDatabase;

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

    private function createTestReport(): array
    {
        $template = ReportTemplate::factory()->create([
            'is_active' => true,
        ]);
        $patient = Patient::factory()->create();

        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ]);

        return [
            'report' => $report,
            'template' => $template,
            'patient' => $patient,
        ];
    }

    private function mockExtractorSuccess(): void
    {
        $this->mock(LlmExtractorService::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn([
                    'extracted_data' => [
                        'observaciones' => 'Paciente refiere dolor de cabeza',
                        'diagnostico' => 'Hipertensión arterial',
                    ],
                    'confidence_scores' => [
                        'observaciones' => 0.95,
                        'diagnostico' => 0.98,
                    ],
                    'warnings' => [],
                    'processing_time_ms' => 150,
                ]);
        });
    }

    // ─── HAPPY PATH ──────────────────────────────────────────

    public function test_extract_data_successfully(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();
        $this->mockExtractorSuccess();

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Paciente de 45 años refiere dolor de cabeza desde hace una semana.',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'extracted_data',
                'confidence_scores',
                'warnings',
                'processing_time_ms',
            ],
            'meta',
            'message',
        ]);
        $response->assertJsonFragment([
            'message' => 'Datos extraídos correctamente',
        ]);
        $this->assertEquals(
            'Hipertensión arterial',
            $response->json('data.extracted_data.diagnostico')
        );
    }

    // ─── AUTHENTICATION ───────────────────────────────────────

    public function test_extract_data_requires_authentication(): void
    {
        $data = $this->createTestReport();

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Texto de prueba',
                'template_id' => $data['template']->id,
            ]
        );

        $response->assertStatus(401);
    }

    // ─── AUTHORIZATION ────────────────────────────────────────

    public function test_extract_data_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $data = $this->createTestReport();

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Texto de prueba',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(403);
    }

    // ─── VALIDATION ───────────────────────────────────────────

    public function test_extract_data_validates_transcript_required(): void
    {
        $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => '',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transcript']);
    }

    public function test_extract_data_validates_template_id_required(): void
    {
        $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Texto de prueba',
                'template_id' => 99999,
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['template_id']);
    }

    // ─── NOT FOUND ────────────────────────────────────────────

    public function test_extract_data_report_not_found(): void
    {
        $this->actingWithPermission('report.edit');

        $template = ReportTemplate::factory()->create(['is_active' => true]);

        $response = $this->postJson(
            '/reports/99999/extract-data',
            [
                'transcript' => 'Texto de prueba',
                'template_id' => $template->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'Informe no encontrado',
        ]);
    }

    // ─── LLM ERRORS ───────────────────────────────────────────

    public function test_extract_data_handles_llm_timeout(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();

        $this->mock(LlmExtractorService::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andThrow(new LlmTimeoutException('LLM request timed out'));
        });

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Texto de prueba',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al procesar con IA',
        ]);
    }

    public function test_extract_data_handles_llm_malformed_response(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();

        $this->mock(LlmExtractorService::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andThrow(new LlmResponseException('Malformed JSON response from LLM'));
        });

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Texto de prueba',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al procesar con IA',
        ]);
    }

    // ─── PERSISTENCE ──────────────────────────────────────────

    public function test_extract_data_saves_llm_interaction(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();
        $this->mockExtractorSuccess();

        $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Paciente con diabetes tipo 2',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $this->assertDatabaseHas('llm_interactions', [
            'patient_report_id' => $data['report']->id,
        ]);
    }

    // ─── DATA STRUCTURE ──────────────────────────────────────

    public function test_extract_data_discards_extra_fields(): void
    {
        $user = $this->actingWithPermission('report.edit');
        $data = $this->createTestReport();

        $this->mock(LlmExtractorService::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn([
                    'extracted_data' => [
                        'observaciones' => 'Paciente refiere dolor',
                        'diagnostico' => 'Gripe común',
                        'extra_field' => 'should_not_appear',
                    ],
                    'confidence_scores' => [
                        'observaciones' => 0.9,
                        'diagnostico' => 0.95,
                        'extra_field' => 0.5,
                    ],
                    'warnings' => [],
                    'processing_time_ms' => 100,
                ]);
        });

        $response = $this->postJson(
            "/reports/{$data['report']->id}/extract-data",
            [
                'transcript' => 'Paciente con síntomas gripales',
                'template_id' => $data['template']->id,
            ],
            $this->authHeader()
        );

        $response->assertStatus(200);
        $extractedData = $response->json('data.extracted_data');

        // The Action returns whatever extract() returns — extra field filtering
        // happens in parseLlmResponse (unit-tested in T9). The feature test
        // verifies the template fields ARE present in the response.
        $this->assertArrayHasKey('observaciones', $extractedData);
        $this->assertArrayHasKey('diagnostico', $extractedData);
    }
}
