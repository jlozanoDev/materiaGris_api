<?php

namespace App\Commands\Admin\Clinic;

use App\Models\Clinic;
use App\Repositories\Clinic\ClinicSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\UploadedFile;

class UploadClinicLogoCommand
{
    public function __construct(
        private ClinicSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(UploadedFile $file): Clinic
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.clinic.update');

        return $this->repo->updateLogo($file);
    }
}
