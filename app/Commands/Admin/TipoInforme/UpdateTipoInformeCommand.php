<?php

namespace App\Commands\Admin\TipoInforme;

use App\Repositories\TipoInforme\TipoInformeSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class UpdateTipoInformeCommand
{
    public function __construct(
        private TipoInformeSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id, array $data): ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.tipoinforme.update');

        return $this->repo->actualizar($id, $data);
    }
}
