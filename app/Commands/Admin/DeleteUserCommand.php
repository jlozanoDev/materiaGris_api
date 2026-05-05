<?php

namespace App\Commands\Admin;

use App\Repositories\User\GetUserRepository;
use App\Repositories\User\SaveUserRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class DeleteUserCommand
{
    private GetUserRepository $leer;
    private SaveUserRepository $escribir;

    public function __construct(GetUserRepository $leer, SaveUserRepository $escribir)
    {
        $this->leer = $leer;
        $this->escribir = $escribir;
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

        $permissionService = app(PermissionService::class);
        $permissionService->ensure($actor, 'admin.user.delete');

        $user = $this->leer->buscarPorId($id);
        if (! $user) return false;
        $this->escribir->eliminar($user);
        return true;
    }
}
