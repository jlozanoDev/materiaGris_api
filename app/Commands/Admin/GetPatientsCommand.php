<?php

namespace App\Commands\Admin;

use App\Repositories\Patient\PatientReadRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetPatientsCommand
{
    public function __construct(
        private PatientReadRepository $leer,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $filters = [])
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'patient.view');

        return $this->leer->buscarPorFiltros($filters);
    }
}
