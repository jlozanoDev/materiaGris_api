<?php

namespace App\Commands\Admin\Role;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetRolesCommand
{
    private RoleRepository $repository;
    private PermissionService $permissionService;

    public function __construct(RoleRepository $repository, PermissionService $permissionService)
    {
        $this->repository = $repository;
        $this->permissionService = $permissionService;
    }

    public function execute()
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.role.view');

        return $this->repository->listarTodos()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
                'users_count' => $role->users_count,
            ];
        });
    }
}
