<?php

namespace App\Commands\Admin;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetRoleCommand
{
    private RoleRepository $repository;

    public function __construct(RoleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $id)
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $permissionService = app(PermissionService::class);
        $permissionService->ensure($user, 'admin.role.view');

        $role = $this->repository->buscarPorId($id);
        if (!$role) {
            throw new \Exception('Rol no encontrado', 404);
        }

        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_system' => (bool) $role->is_system,
            'permissions' => $role->permissions->map(function($p) {
                return [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'grant' => (int) $p->pivot->grant
                ];
            })
        ];
    }
}
