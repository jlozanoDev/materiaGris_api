<?php

namespace App\Commands\Admin;

use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Exceptions\PermissionDeniedException;

class CreateRoleCommand
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

    public function execute(array $data)
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'admin.role.create');

        $role = $this->repository->guardar($data);

        $this->auditService->record(
            'role.create',
            $user,
            $role,
            ['payload' => $data],
            ['module' => 'security']
        );

        return $role;
    }
}
