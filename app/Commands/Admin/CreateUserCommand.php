<?php

namespace App\Commands\Admin;

use App\Repositories\User\SaveUserRepository;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\PasswordResetService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Log;

class CreateUserCommand
{
    private SaveUserRepository $saveRepo;
    private PermissionService $permissionService;
    private PasswordResetService $passwordResetService;

    public function __construct(
        SaveUserRepository $saveRepo,
        PermissionService $permissionService,
        PasswordResetService $passwordResetService,
    ) {
        $this->saveRepo = $saveRepo;
        $this->permissionService = $permissionService;
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Create a new user and return the User model.
     * Expected $data contains at least: name, email
     */
    public function execute(array $data): User
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.user.create');

        $newUser = $this->saveRepo->crear($data);

        try {
            $this->passwordResetService->solicitarReseteo($newUser->email);
        } catch (\Throwable $e) {
            Log::error('[CreateUserCommand] error sending reset email: ' . $e->getMessage());
        }

        return $newUser;
    }
}
