<?php

namespace App\Commands\Admin\Clinic;

use App\Models\Clinic;
use App\Repositories\Clinic\ClinicSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class UpdateClinicCommand
{
    public function __construct(
        private ClinicSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(array $data): Clinic
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.clinic.update');

        return $this->repo->update($data);
    }
}
