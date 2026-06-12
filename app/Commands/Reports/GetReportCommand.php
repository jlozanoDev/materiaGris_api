<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;

class GetReportCommand
{
    public function __construct(
        private PatientReportReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.view');

        $report = $this->repo->buscarPorId($id);
        if (! $report) {
            throw new \RuntimeException('Informe no encontrado');
        }

        return $report;
    }
}
