<?php

namespace App\Commands\Admin\ReportTemplate;

use App\Repositories\ReportTemplate\ReportTemplateReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListReportTemplatesCommand
{
    public function __construct(
        private ReportTemplateReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.reporttemplate.view');

        return $this->repo->listar($filters);
    }
}
