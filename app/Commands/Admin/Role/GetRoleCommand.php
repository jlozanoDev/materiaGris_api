<?php

namespace App\Commands\Admin\Role;

use App\DTOs\RoleDetail;
use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetRoleCommand
{
    private RoleRepository $repository;
    private PermissionService $permissionService;

    public function __construct(RoleRepository $repository, PermissionService $permissionService)
    {
        $this->repository = $repository;
        $this->permissionService = $permissionService;
    }

    public function execute(int $id): RoleDetail
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.role.view');

        $role = $this->repository->buscarPorId($id);
        if (!$role) {
            throw new \RuntimeException('Rol no encontrado', 404);
        }

        return new RoleDetail(
            id: $role->id,
            name: $role->name,
            slug: $role->slug,
            description: $role->description,
            isSystem: (bool) $role->is_system,
            permissions: $role->permissions->map(fn ($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'grant' => (int) $p->pivot->grant,
            ])->toArray(),
        );
    }
}
