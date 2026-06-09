<?php

namespace App\Commands\Admin\ReportTemplate;

use App\Repositories\ReportTemplate\ReportTemplateSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class CreateReportTemplateCommand
{
    public function __construct(
        private ReportTemplateSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $data): ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.reporttemplate.create');

        return $this->repo->crear($data);
    }
}
