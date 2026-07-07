<?php

namespace App\Commands\Reports;

use App\DTOs\PdfFileInfo;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
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

        if (! $report->pdf_path || ! Storage::disk('local')->exists($report->pdf_path)) {
            throw new \RuntimeException('El PDF no está disponible. Utilice la vista del informe para generar el PDF.');
        }

        $fullPath = Storage::disk('local')->path($report->pdf_path);

        return new PdfFileInfo(
            path: $fullPath,
            filename: 'informe_' . $report->id . '.pdf',
        );
    }
}
