<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;

class InitReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $data): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.create');

        return $this->repo->iniciar($data);
    }
}
