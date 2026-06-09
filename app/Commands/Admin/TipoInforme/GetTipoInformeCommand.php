<?php

namespace App\Commands\Admin\TipoInforme;

use App\Repositories\TipoInforme\TipoInformeReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportTemplate;

class GetTipoInformeCommand
{
    public function __construct(
        private TipoInformeReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id): ?ReportTemplate
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.tipoinforme.view');

        return $this->repo->buscarPorId($id);
    }
}
