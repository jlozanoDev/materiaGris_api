<?php

namespace App\Commands\Admin\ReportTemplate;

use App\Repositories\ReportTemplate\ReportTemplateSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class UpdateReportTemplateCommand
{
    public function __construct(
        private ReportTemplateSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id, array $data): ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.reporttemplate.update');

        return $this->repo->actualizar($id, $data);
    }
}
