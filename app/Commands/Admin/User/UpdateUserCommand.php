<?php

namespace App\Commands\Admin\User;

use App\Exceptions\PermissionDeniedException;
use App\Models\User;
use App\Repositories\Permission\GetPermissionRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\GetUserRepository;
use App\Repositories\User\SaveUserRepository;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\RoleAssignmentService;
use Illuminate\Support\Facades\DB;

class UpdateUserCommand
{
    private GetUserRepository $leer;
    private SaveUserRepository $escribir;
    private PermissionService $permissionService;
    private RoleAssignmentService $roleAssignmentService;
    private AuditService $auditService;
    private RoleRepository $roleRepository;
    private GetPermissionRepository $permissionRepository;

    public function __construct(
        GetUserRepository $leer,
        SaveUserRepository $escribir,
        PermissionService $permissionService,
        RoleAssignmentService $roleAssignmentService,
        AuditService $auditService,
        RoleRepository $roleRepository,
        GetPermissionRepository $permissionRepository,
    ) {
        $this->leer = $leer;
        $this->escribir = $escribir;
        $this->permissionService = $permissionService;
        $this->roleAssignmentService = $roleAssignmentService;
        $this->auditService = $auditService;
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * @param int $id User ID to update
     * @param array{name?:string,email?:string,active?:bool,roles?:array<int>,roles_remove?:array<int>,permissions?:array{permission_id:int,grant:int}[]} $data
     * @return array{id:int,name:string,email:string,active:bool,roles:array{id:int,name:string,slug:string,is_system:bool}[],user_permissions:array{permission_id:int,slug:string,grant:int,origin:string,origin_id:?int}[],effective_permissions:array<string,int>}|null
     * @throws \Exception
     */
    public function execute(int $id, array $data): ?array
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($actor, 'admin.user.update');

        $user = $this->leer->buscarPorId($id);
        if (! $user instanceof User) {
            return null;
        }

        $data = $this->procesarRoles($user, $data, $actor);
        $data = $this->procesarPermisos($user, $data, $actor);

        $this->escribir->actualizar($user, $data);
        $this->permissionService->invalidateCache($user);

        return $this->construirRespuesta($user);
    }

    /**
     * @throws \Exception
     */
    private function procesarRoles(User $user, array $data, User $actor): array
    {
        if (isset($data['roles_remove'])) {
            foreach ($data['roles_remove'] as $roleId) {
                $role = $this->roleRepository->buscarPorId($roleId);
                if (! $role) {
                    continue;
                }

                if ($role->is_system && $user->id === $actor->id) {
                    throw new \RuntimeException('No puedes quitar roles de sistema de ti mismo.');
                }

                $this->roleAssignmentService->revokeRoleFromUser($user, $role, $actor);
            }
            unset($data['roles_remove']);
        }

        if (isset($data['roles'])) {
            foreach ($data['roles'] as $roleId) {
                $role = $this->roleRepository->buscarPorId($roleId);
                if (! $role) {
                    continue;
                }

                if ($role->is_system && $user->id === $actor->id) {
                    throw new \RuntimeException('No puedes asignarte roles de sistema a ti mismo.');
                }

                $this->roleAssignmentService->assignRoleToUser($user, $role, $actor);
            }
            unset($data['roles']);
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    private function procesarPermisos(User $user, array $data, User $actor): array
    {
        if (! isset($data['permissions'])) {
            return $data;
        }

        $user->load('userPermissions');
        $user->load('roles.permissions');

        $overridesByPermId = [];
        foreach ($user->userPermissions as $up) {
            if ($up->pivot->origin === 'user') {
                $overridesByPermId[$up->id] = (int) $up->pivot->grant;
            }
        }

        $rolePermsByPermId = [];
        foreach ($user->roles as $role) {
            $role->loadMissing('permissions');
            foreach ($role->permissions as $perm) {
                $rolePermsByPermId[$perm->id] = (int) $perm->pivot->grant;
            }
        }

        DB::transaction(function () use ($user, $data, $actor, $overridesByPermId, $rolePermsByPermId) {
            foreach ($data['permissions'] as $override) {
                $permissionId = $override['permission_id'];
                $grant = $override['grant'];

                // Si grant es 0, eliminar el override
                if ($grant === 0) {
                    $user->userPermissions()->newPivotStatement()
                        ->where('user_id', $user->id)
                        ->where('permission_id', $permissionId)
                        ->where('origin', 'user')
                        ->delete();
                    
                    $this->auditService->record(
                        'user.permission.removed',
                        $actor,
                        $user,
                        ['permission_id' => $permissionId, 'reason' => 'grant_set_to_zero'],
                        ['module' => 'permissions']
                    );
                    continue;
                }

                $exists = isset($overridesByPermId[$permissionId]);
                $roleGrant = $rolePermsByPermId[$permissionId] ?? null;

                if ($exists && $overridesByPermId[$permissionId] === -1 && $roleGrant === 1) {
                    $perm = $this->permissionRepository->buscarPorId($permissionId);
                    throw new \RuntimeException(
                        sprintf(
                            'Conflicto: el usuario tiene override de denegación (-1) para "%s", pero el rol tiene grant (+1). Elimina primero el override o cambia el rol.',
                            $perm?->slug ?? $permissionId
                        )
                    );
                }

                if ($exists && $overridesByPermId[$permissionId] === 1 && $roleGrant === 1) {
                    $user->userPermissions()->newPivotStatement()
                        ->where('user_id', $user->id)
                        ->where('permission_id', $permissionId)
                        ->where('origin', 'user')
                        ->delete();

                    $this->auditService->record(
                        'user.permission.removed',
                        $actor,
                        $user,
                        ['permission_id' => $permissionId],
                        ['module' => 'permissions', 'reason' => 'automatic_role_conflict_resolution']
                    );

                    continue;
                }

                $user->userPermissions()->syncWithoutDetaching([
                    $permissionId => [
                        'grant' => $grant,
                        'origin' => 'user',
                        'origin_id' => null,
                        'applied_by' => $actor->id,
                    ],
                ]);

                $this->auditService->record(
                    'user.permission.updated',
                    $actor,
                    $user,
                    ['permission_id' => $permissionId, 'grant' => $grant],
                    ['module' => 'permissions']
                );
            }
        });

        unset($data['permissions']);

        return $data;
    }

    /**
     * @return array{id:int,name:string,email:string,active:bool,roles:array{id:int,name:string,slug:string,is_system:bool}[],user_permissions:array{permission_id:int,slug:string,grant:int,origin:string,origin_id:?int}[],effective_permissions:array<string,int>}
     */
    private function construirRespuesta(User $user): array
    {
        $user->load('roles');
        $user->load('userPermissions');

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
