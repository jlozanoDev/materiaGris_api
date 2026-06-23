<?php

namespace App\Commands\Reports;

use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TemplateNotFoundException;
use App\Models\LlmInteraction;
use App\Models\PatientReport;
use App\Models\User;
use App\Repositories\Report\PatientReportReadRepository;
use App\Repositories\ReportTemplate\ReportTemplateReadRepository;
use App\Services\LlmExtractorService;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExtractReportDataCommand
{
    public function __construct(
        private readonly PatientReportReadRepository $reportRepository,
        private readonly ReportTemplateReadRepository $templateRepository,
        private readonly PermissionService $permissionService,
        private readonly LlmExtractorService $llmService,
    ) {}

    /**
     * Execute the extract report data use case.
     *
     * @param int    $reportId   The patient report ID
     * @param string $transcript The consultation transcript
     * @param int    $templateId The report template ID
     * @param User   $user       The authenticated user
     * @return array{extracted_data: array, confidence_scores: array, warnings: array, processing_time_ms: int}
     *
     * @throws PermissionDeniedException
     * @throws ModelNotFoundException
     * @throws TemplateNotFoundException
     */
    public function execute(int $reportId, string $transcript, int $templateId, User $user): array
    {
        $this->permissionService->ensure($user, 'report.edit');

        $report = $this->reportRepository->buscarPorId($reportId);

        if (! $report) {
            throw new ModelNotFoundException('Informe no encontrado');
        }

        $template = $this->templateRepository->buscarPorId($templateId);

        if (! $template) {
            throw new TemplateNotFoundException('Plantilla no válida');
        }

        if (! $template->is_active) {
            throw new TemplateNotFoundException('Plantilla no válida');
        }

        $templateStructure = $report->template_structure_snapshot ?? [];

        $patientContext = $this->buildPatientContext($report);

        // Build request metadata (PII-safe — no transcript text)
        $requestPayload = [
            'template_field_count' => is_countable($templateStructure['sections'] ?? [])
                ? count($templateStructure['sections'])
                : 0,
            'transcript_length' => mb_strlen($transcript),
            'patient_context_keys' => array_keys($patientContext),
        ];

        $startTime = microtime(true);

        try {
            $result = $this->llmService->extract($templateStructure, $transcript, $patientContext);
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            LlmInteraction::create([
                'patient_report_id' => $reportId,
                'request_payload' => $requestPayload,
                'response_payload' => $result,
                'processing_time_ms' => $processingTimeMs,
            ]);

            return $result;
        } catch (\Exception $e) {
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            LlmInteraction::create([
                'patient_report_id' => $reportId,
                'request_payload' => $requestPayload,
                'response_payload' => [
                    'error' => class_basename($e),
                    'message' => $e->getMessage(),
                ],
                'processing_time_ms' => $processingTimeMs,
            ]);

            throw $e;
        }
    }

    /**
     * Build the patient context array for the LLM prompt.
     *
     * @param PatientReport $report
     * @return array{edad: int|null, sexo: string|null, medicacion: string, last_reports: array}
     */
    private function buildPatientContext(PatientReport $report): array
    {
        $patient = $report->patient;

        $edad = $patient?->age;
        $sexo = $patient?->gender;

        $lastReports = PatientReport::where('patient_id', $report->patient_id)
            ->where('id', '!=', $report->id)
            ->latest()
            ->take(10)
            ->get()
            ->pluck('values')
            ->toArray();

        $medicationValues = [];
        foreach ($lastReports as $reportValues) {
            if (is_array($reportValues)) {
                foreach ($reportValues as $key => $value) {
                    if (str_contains(strtolower($key), 'medic')) {
                        $medicationValues[] = $value;
                    }
                }
            }
        }

        $medicacion = ! empty($medicationValues)
            ? implode(', ', array_filter($medicationValues))
            : 'No reportada';

        return [
            'edad' => $edad,
            'sexo' => $sexo,
            'medicacion' => $medicacion,
            'last_reports' => $lastReports,
        ];
    }
}
