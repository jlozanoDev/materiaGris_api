<?php

namespace App\Commands\Auth;

use App\Repositories\User\GetUserRepository;
use App\Models\User;
use App\Services\PermissionService;
use Carbon\Carbon;

class MeCommand
{
    private GetUserRepository $leer;
    private PermissionService $permissionService;

    public function __construct(GetUserRepository $leer, PermissionService $permissionService)
    {
        $this->leer = $leer;
        $this->permissionService = $permissionService;
    }

    public function execute(int $userId): ?array
    {
        $user = $this->leer->buscarPorId($userId);
        if (! $user) {
            return null;
        }

        $roles = $user->roles->pluck('slug')->all();
        $permissions = $this->permissionService->getEffectivePermissions($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
            'permissions' => $permissions,
            'permissions_version' => Carbon::now()->toIso8601String(),
        ];
    }
}
