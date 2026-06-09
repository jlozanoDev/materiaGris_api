<?php

namespace App\Commands\Admin\TipoInforme;

use App\Repositories\TipoInforme\TipoInformeSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class CreateTipoInformeCommand
{
    public function __construct(
        private TipoInformeSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $data): ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.tipoinforme.create');

        return $this->repo->crear($data);
    }
}
