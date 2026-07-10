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

        $roles = $user->roles->map(fn ($role) => [
            'id'   => $role->id,
            'name' => $role->name,
        ])->values()->all();
        $permissions = $this->permissionService->getEffectivePermissions($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'apellido' => $user->apellido,
            'email' => $user->email,
            'num_colegiado' => $user->num_colegiado,
            'especialidad' => $user->especialidad,
            'telefono' => $user->telefono,
            'roles' => $roles,
            'permissions' => $permissions,
            'permissions_version' => Carbon::now()->toIso8601String(),
        ];
    }
}
