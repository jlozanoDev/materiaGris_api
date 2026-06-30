<?php

namespace App\Commands\Admin\User;

use App\DTOs\UserDetail;
use App\Models\User;
use App\Repositories\User\GetUserRepository;
use App\Services\PermissionService;

class GetUserCommand
{
    private GetUserRepository $leer;
    private PermissionService $permissionService;

    public function __construct(GetUserRepository $leer, PermissionService $permissionService)
    {
        $this->leer = $leer;
        $this->permissionService = $permissionService;
    }

    public function execute(int $id): ?UserDetail
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new \App\Exceptions\PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($actor, 'admin.user.view');

        $user = $this->leer->buscarPorId($id);
        if (! $user instanceof User) {
            return null;
        }

        return UserDetail::fromUser($user, $this->permissionService);
    }
}
