<?php

namespace App\Commands\Reports;

use App\Repositories\ReportTemplate\ReportTemplateReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Collection;

class GetActiveTemplatesCommand
{
    public function __construct(
        private ReportTemplateReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.create');

        return $this->repo->listarActivas();
    }
}
