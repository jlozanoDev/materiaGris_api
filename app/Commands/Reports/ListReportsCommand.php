<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListReportsCommand
{
    public function __construct(
        private PatientReportReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.view');

        return $this->repo->listar($filters);
    }
}
