<?php

namespace App\Commands\Admin\ReportTemplate;

use App\Repositories\ReportTemplate\ReportTemplateReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class GetReportTemplateCommand
{
    public function __construct(
        private ReportTemplateReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): ?ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.reporttemplate.view');

        return $this->repo->buscarPorId($id);
    }
}
