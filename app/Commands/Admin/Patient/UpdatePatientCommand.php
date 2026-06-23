<?php

namespace App\Commands\Admin\Patient;

use App\Repositories\Patient\SavePatientRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\Patient;

class UpdatePatientCommand
{
    public function __construct(
        private SavePatientRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute($id, array $data): Patient
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'patient.update');

        return $this->repo->actualizar($id, $data);
    }
}
