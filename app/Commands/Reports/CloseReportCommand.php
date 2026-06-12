<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CloseReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.close');

        $report = PatientReport::findOrFail($id);

        if ($report->status !== ReportStatus::Signed) {
            throw new \RuntimeException('Solo se pueden cerrar informes firmados');
        }

        if ($report->user_id !== $user->id) {
            throw new PermissionDeniedException('Solo el autor puede cerrar este informe');
        }

        $pdfPath = $this->generatePdf($report);

        return $this->repo->cerrar($id, $pdfPath);
    }

    private function generatePdf(PatientReport $report): string
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report->load(['patient', 'user']),
        ]);

        $filename = 'reports/report_' . $report->id . '_' . time() . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
