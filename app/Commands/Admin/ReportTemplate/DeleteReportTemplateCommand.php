<?php

namespace App\Commands\Admin\ReportTemplate;

use App\Repositories\ReportTemplate\ReportTemplateSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class DeleteReportTemplateCommand
{
    public function __construct(
        private ReportTemplateSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    /**
     * Soft-delete a template.
     *
     * @param int $id
     * @return void
     * @throws PermissionDeniedException
     * @throws \RuntimeException when template not found or has referenced reports
     */
    public function execute(int $id): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.reporttemplate.delete');

        $this->repo->eliminar($id);
    }
}
