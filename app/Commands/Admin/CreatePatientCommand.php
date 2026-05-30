<?php

namespace App\Commands\Admin;

use App\Repositories\Patient\SavePatientRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\Patient;

class CreatePatientCommand
{
    public function __construct(
        private SavePatientRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $data): Patient
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'patient.create');

        return $this->repo->crear($data);
    }
}
