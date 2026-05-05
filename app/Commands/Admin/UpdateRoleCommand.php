<?php

namespace App\Commands\Admin;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Exceptions\PermissionDeniedException;

class UpdateRoleCommand
{
    private RoleRepository $repository;
    private AuditService $auditService;
    private PermissionService $permissionService;

    public function __construct(
        RoleRepository $repository, 
        AuditService $auditService,
        PermissionService $permissionService
    ) {
        $this->repository = $repository;
        $this->auditService = $auditService;
        $this->permissionService = $permissionService;
    }

    public function execute(int $id, array $data)
    {
        $userActor = auth()->user();
        if (! $userActor) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($userActor, 'admin.role.update');

        $role = $this->repository->buscarPorId($id);
        if (!$role) {
            throw new \Exception('Rol no encontrado', 404);
        }

        // Capture previous state for audit
        $previousPermissions = $role->permissions->mapWithKeys(function($p) {
            return [$p->slug => (int) $p->pivot->grant];
        })->toArray();

        // Guardar cambios
        $role = $this->repository->guardar($data, $role);

        // Invalidate cache for all users with this role
        $role->load('users');
        foreach ($role->users as $u) {
            $this->permissionService->invalidateCache($u);
        }

        // New state for audit
        $role->load('permissions');
        $newPermissions = $role->permissions->mapWithKeys(function($p) {
            return [$p->slug => (int) $p->pivot->grant];
        })->toArray();

        $this->auditService->record(
            'role.update',
            $userActor,
            $role,
            [
                'payload' => $data,
                'diff' => [
                    'before' => $previousPermissions,
                    'after' => $newPermissions
                ]
            ],
            ['module' => 'security']
        );

        return $role;
    }
}
