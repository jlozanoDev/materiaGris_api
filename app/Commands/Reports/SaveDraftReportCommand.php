<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;

class SaveDraftReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id, array $data): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.edit');

        $report = PatientReport::findOrFail($id);

        if ($report->status !== ReportStatus::Draft) {
            throw new \RuntimeException('Solo se puede editar informes en estado borrador');
        }

        if ($report->user_id !== $user->id) {
            throw new PermissionDeniedException('Solo el autor puede editar este informe');
        }

        return $this->repo->actualizarValores($id, $data['values']);
    }
}
