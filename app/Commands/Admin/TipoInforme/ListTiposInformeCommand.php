<?php

namespace App\Commands\Admin\TipoInforme;

use App\Repositories\TipoInforme\TipoInformeReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTiposInformeCommand
{
    public function __construct(
        private TipoInformeReadRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.tipoinforme.view');

        return $this->repo->listar($filters);
    }
}
