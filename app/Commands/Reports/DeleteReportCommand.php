<?php

namespace App\Commands\Reports;

use App\Enums\ReportStatus;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Storage;

class DeleteReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.delete');

        $report = PatientReport::findOrFail($id);

        if ($report->status !== ReportStatus::Draft) {
            throw new \RuntimeException('Solo se pueden eliminar informes en estado borrador');
        }

        if ($report->user_id !== $user->id) {
            throw new PermissionDeniedException('Solo el autor puede eliminar este informe');
        }

        if ($report->signature_path && Storage::disk('local')->exists($report->signature_path)) {
            Storage::disk('local')->delete($report->signature_path);
        }

        if ($report->pdf_path && Storage::disk('local')->exists($report->pdf_path)) {
            Storage::disk('local')->delete($report->pdf_path);
        }

        $this->repo->delete($id);
    }
}
