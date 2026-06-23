<?php

namespace Tests\Unit\Commands;

use App\Commands\Reports\ExtractReportDataCommand;
use App\Repositories\Report\PatientReportReadRepository;
use App\Repositories\ReportTemplate\ReportTemplateReadRepository;
use App\Services\PermissionService;
use App\Services\LlmExtractorService;
use App\Exceptions\LlmTimeoutException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TemplateNotFoundException;
use App\Models\LlmInteraction;
use App\Models\PatientReport;
use App\Models\ReportTemplate;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExtractReportDataCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_execute_with_valid_data_returns_extracted_data(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'date_of_birth' => '1980-01-01',
            'gender' => 'Masculino',
        ]);

        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'template_structure_snapshot' => ['sections' => []],
            'values' => ['nota' => 'valor anterior'],
        ]);

        $template = ReportTemplate::factory()->create([
            'is_active' => true,
        ]);

        // Mock the LLM service
        $expectedLlmResult = [
            'extracted_data' => ['motivo_consulta' => 'Dolor de cabeza'],
            'confidence_scores' => ['motivo_consulta' => 1.0],
            'warnings' => [],
            'processing_time_ms' => 150,
        ];

        $llmService = $this->createMock(LlmExtractorService::class);
        $llmService->expects($this->once())
            ->method('extract')
            ->with(
                ['sections' => []],
                'Paciente presenta dolor de cabeza',
                $this->callback(function ($context) {
                    return isset($context['edad'], $context['sexo'], $context['medicacion'], $context['last_reports'])
                        && $context['edad'] === 46
                        && $context['sexo'] === 'Masculino';
                })
            )
            ->willReturn($expectedLlmResult);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with($report->id)
            ->willReturn($report);

        $templateRepo = $this->createMock(ReportTemplateReadRepository::class);
        $templateRepo->expects($this->once())
            ->method('buscarPorId')
            ->with($template->id)
            ->willReturn($template);

        $command = new ExtractReportDataCommand(
            $reportRepo,
            $templateRepo,
            $permissionService,
            $llmService,
        );

        $result = $command->execute($report->id, 'Paciente presenta dolor de cabeza', $template->id, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('extracted_data', $result);
        $this->assertEquals('Dolor de cabeza', $result['extracted_data']['motivo_consulta']);

        $this->assertDatabaseHas('llm_interactions', [
            'patient_report_id' => $report->id,
        ]);
    }

    #[Test]
    public function test_execute_throws_permission_denied_when_user_lacks_permission(): void
    {
        $user = User::factory()->make(['id' => 2]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit')
            ->willThrowException(new PermissionDeniedException('User lacks required permission: report.edit'));

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->never())->method('buscarPorId');

        $templateRepo = $this->createMock(ReportTemplateReadRepository::class);
        $templateRepo->expects($this->never())->method('buscarPorId');

        $llmService = $this->createMock(LlmExtractorService::class);
        $llmService->expects($this->never())->method('extract');

        $command = new ExtractReportDataCommand(
            $reportRepo,
            $templateRepo,
            $permissionService,
            $llmService,
        );

        $this->expectException(PermissionDeniedException::class);

        $command->execute(1, 'Paciente presenta dolor', 5, $user);
    }

    #[Test]
    public function test_execute_throws_template_not_found_when_template_does_not_exist(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $report = $this->createMock(PatientReport::class);
        $report->method('__get')->willReturnCallback(function ($key) {
            return match ($key) {
                'id' => 1,
                'patient_id' => 10,
                'template_structure_snapshot' => ['sections' => []],
                default => null,
            };
        });

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with(1)
            ->willReturn($report);

        $templateRepo = $this->createMock(ReportTemplateReadRepository::class);
        $templateRepo->expects($this->once())
            ->method('buscarPorId')
            ->with(999)
            ->willReturn(null);

        $llmService = $this->createMock(LlmExtractorService::class);
        $llmService->expects($this->never())->method('extract');

        $command = new ExtractReportDataCommand(
            $reportRepo,
            $templateRepo,
            $permissionService,
            $llmService,
        );

        $this->expectException(TemplateNotFoundException::class);

        $command->execute(1, 'Paciente presenta dolor', 999, $user);
    }

    #[Test]
    public function test_execute_throws_template_not_found_when_template_is_inactive(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $report = $this->createMock(PatientReport::class);
        $report->method('__get')->willReturnCallback(function ($key) {
            return match ($key) {
                'id' => 1,
                'patient_id' => 10,
                'template_structure_snapshot' => ['sections' => []],
                default => null,
            };
        });

        $template = $this->createMock(ReportTemplate::class);
        $template->method('__get')->willReturnCallback(function ($key) {
            return match ($key) {
                'id' => 5,
                'is_active' => false,
                default => null,
            };
        });

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with(1)
            ->willReturn($report);

        $templateRepo = $this->createMock(ReportTemplateReadRepository::class);
        $templateRepo->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($template);

        $llmService = $this->createMock(LlmExtractorService::class);
        $llmService->expects($this->never())->method('extract');

        $command = new ExtractReportDataCommand(
            $reportRepo,
            $templateRepo,
            $permissionService,
            $llmService,
        );

        $this->expectException(TemplateNotFoundException::class);

        $command->execute(1, 'Paciente presenta dolor', 5, $user);
    }

    #[Test]
    public function test_execute_saves_llm_interaction_on_llm_failure(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'date_of_birth' => '1980-01-01',
            'gender' => 'Masculino',
        ]);

        $report = PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'template_structure_snapshot' => ['sections' => [['id' => 1, 'label' => 'Section 1']]],
            'values' => [],
        ]);

        $template = ReportTemplate::factory()->create([
            'is_active' => true,
        ]);

        // Mock LLM service to throw a timeout exception
        $llmService = $this->createMock(LlmExtractorService::class);
        $llmService->expects($this->once())
            ->method('extract')
            ->willThrowException(new LlmTimeoutException('LLM request timed out after 30 seconds'));

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('ensure')
            ->with($user, 'report.edit');

        $reportRepo = $this->createMock(PatientReportReadRepository::class);
        $reportRepo->expects($this->once())
            ->method('buscarPorId')
            ->with($report->id)
            ->willReturn($report);

        $templateRepo = $this->createMock(ReportTemplateReadRepository::class);
        $templateRepo->expects($this->once())
            ->method('buscarPorId')
            ->with($template->id)
            ->willReturn($template);

        $command = new ExtractReportDataCommand(
            $reportRepo,
            $templateRepo,
            $permissionService,
            $llmService,
        );

        try {
            $command->execute($report->id, 'Paciente presenta dolor', $template->id, $user);
            $this->fail('Expected LlmTimeoutException was not thrown');
        } catch (LlmTimeoutException $e) {
            // Verify the LlmInteraction was saved despite the exception
            $this->assertDatabaseHas('llm_interactions', [
                'patient_report_id' => $report->id,
            ]);

            $interaction = LlmInteraction::where('patient_report_id', $report->id)->first();
            $this->assertNotNull($interaction);

            // Verify request_payload metadata
            $requestPayload = $interaction->request_payload;
            $this->assertArrayHasKey('template_field_count', $requestPayload);
            $this->assertArrayHasKey('transcript_length', $requestPayload);
            $this->assertArrayHasKey('patient_context_keys', $requestPayload);

            // Verify response_payload contains error info (not null)
            $responsePayload = $interaction->response_payload;
            $this->assertArrayHasKey('error', $responsePayload);
            $this->assertEquals('LlmTimeoutException', $responsePayload['error']);
            $this->assertArrayHasKey('message', $responsePayload);

            // Verify processing_time_ms is set
            $this->assertNotNull($interaction->processing_time_ms);
            $this->assertIsInt($interaction->processing_time_ms);
            $this->assertGreaterThanOrEqual(0, $interaction->processing_time_ms);
        }
    }
}
