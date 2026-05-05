<?php

namespace App\Commands\Admin;

use App\Repositories\User\SaveUserRepository;
use App\Models\User;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class CreateUserCommand
{
    private SaveUserRepository $saveRepo;

    public function __construct(SaveUserRepository $saveRepo)
    {
        $this->saveRepo = $saveRepo;
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

        $permissionService = app(PermissionService::class);
        $permissionService->ensure($user, 'admin.user.create');

        return $this->saveRepo->crear($data);
    }
}
