<?php

namespace App\Commands\Admin;

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

    public function execute(int $id): ?array
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

        $roles = $user->roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'is_system' => (bool) $role->is_system,
        ])->toArray();

        $userPermissions = $user->userPermissions->map(fn ($permission) => [
            'permission_id' => $permission->id,
            'slug' => $permission->slug,
            'grant' => (int) $permission->pivot->grant,
            'origin' => $permission->pivot->origin,
            'origin_id' => $permission->pivot->origin_id,
        ])->toArray();

        $effectivePermissions = $this->permissionService->getEffectivePermissions($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'active' => (bool) $user->active,
            'roles' => $roles,
            'user_permissions' => $userPermissions,
            'effective_permissions' => $effectivePermissions,
        ];
    }
}
