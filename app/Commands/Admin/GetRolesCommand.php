<?php

namespace App\Commands\Admin;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetRolesCommand
{
    private RoleRepository $repository;

    public function __construct(RoleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute()
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $permissionService = app(PermissionService::class);
        $permissionService->ensure($user, 'admin.role.view');

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
