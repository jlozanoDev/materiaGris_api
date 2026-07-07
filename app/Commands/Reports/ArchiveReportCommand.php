<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArchiveReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id, ?UploadedFile $pdfFile = null): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.archive');

        $report = PatientReport::findOrFail($id);

        if ($report->status !== ReportStatus::Signed) {
            throw new \RuntimeException('Solo se pueden archivar informes firmados');
        }

        if ($report->user_id !== $user->id) {
            throw new PermissionDeniedException('Solo el autor puede archivar este informe');
        }

        if (! $pdfFile) {
            throw new \RuntimeException('El archivo PDF es requerido para archivar el informe');
        }

        $filename = 'reports/report_' . $report->id . '_' . time() . '.pdf';
        $pdfFile->storeAs('reports', basename($filename));

        // If there's an old pdf_path, clean it up
        if ($report->pdf_path && Storage::disk('local')->exists($report->pdf_path)) {
            Storage::disk('local')->delete($report->pdf_path);
        }

        return $this->repo->archivar($id, $filename);
    }
}
