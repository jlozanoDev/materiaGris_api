<?php

namespace App\Commands\Admin\User;

use App\Repositories\User\GetUserRepository;
use App\Repositories\User\SaveUserRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class DeleteUserCommand
{
    private GetUserRepository $leer;
    private SaveUserRepository $escribir;
    private PermissionService $permissionService;

    public function __construct(GetUserRepository $leer, SaveUserRepository $escribir, PermissionService $permissionService)
    {
        $this->leer = $leer;
        $this->escribir = $escribir;
        $this->permissionService = $permissionService;
    }

    /**
     * Return true if deleted, false if not found.
     */
    public function execute(int $id): bool
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($actor, 'admin.user.delete');

        $user = $this->leer->buscarPorId($id);
        if (! $user) return false;
        $this->escribir->eliminar($user);
        return true;
    }
}
