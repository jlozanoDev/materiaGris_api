<?php

namespace App\Commands\Reports;

use App\DTOs\PdfFileInfo;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DownloadPdfReportCommand
{
    public function __construct(
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): PdfFileInfo
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.download-pdf');

        $report = PatientReport::with(['patient', 'user'])->findOrFail($id);

        if (! in_array($report->status, [ReportStatus::Signed, ReportStatus::Archived])) {
            throw new \RuntimeException('El PDF solo está disponible para informes firmados o archivados');
        }

        // Regenerate if pdf_path is missing (e.g. signed but not yet archived)
        if (! $report->pdf_path || ! Storage::disk('local')->exists($report->pdf_path)) {
            $pdf = Pdf::loadView('reports.pdf', [
                'report' => $report,
            ]);

            $filename = 'reports/report_' . $report->id . '_' . time() . '.pdf';
            Storage::disk('local')->put($filename, $pdf->output());
            $report->update(['pdf_path' => $filename]);
            $report->refresh();
        }

        $fullPath = Storage::disk('local')->path($report->pdf_path);

        return new PdfFileInfo(
            path: $fullPath,
            filename: 'informe_' . $report->id . '.pdf',
        );
    }
}
