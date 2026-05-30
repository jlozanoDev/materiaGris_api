<?php

namespace App\Commands\Admin;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Exceptions\PermissionDeniedException;

class DeleteRoleCommand
{
    private RoleRepository $repository;
    private AuditService $auditService;
    private PermissionService $permissionService;

    public function __construct(RoleRepository $repository, AuditService $auditService, PermissionService $permissionService)
    {
        $this->repository = $repository;
        $this->auditService = $auditService;
        $this->permissionService = $permissionService;
    }

    public function execute(int $id)
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.role.delete');

        $role = $this->repository->buscarPorId($id);
        if (!$role) {
            throw new \RuntimeException('Rol no encontrado', 404);
        }

        if ($role->is_system) {
            throw new \RuntimeException('No se pueden eliminar roles del sistema', 403);
        }

        $roleData = $role->toArray();
        $this->repository->eliminar($role);

        $this->auditService->record(
            'role.delete',
            $user,
            null,
            ['deleted_role' => $roleData],
            ['module' => 'security', 'target_type' => 'App\Models\Role', 'target_id' => $id]
        );

        return true;
    }
}
