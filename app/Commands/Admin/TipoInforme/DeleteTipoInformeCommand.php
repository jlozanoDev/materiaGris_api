<?php

namespace App\Commands\Admin\TipoInforme;

use App\Repositories\TipoInforme\TipoInformeSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class DeleteTipoInformeCommand
{
    public function __construct(
        private TipoInformeSaveRepository $repo,
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

        $this->permissionService->ensure($user, 'admin.tipoinforme.delete');

        $this->repo->eliminar($id);
    }
}
