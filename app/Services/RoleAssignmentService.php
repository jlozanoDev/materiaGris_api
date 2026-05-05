<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleAssignmentService
{
    private PermissionService $permissionService;
    private AuditService $auditService;

    public function __construct(PermissionService $permissionService, AuditService $auditService)
    {
        $this->permissionService = $permissionService;
        $this->auditService = $auditService;
    }

    /**
     * Assign a role to a user and apply its permissions as origin='role'
     */
    public function assignRoleToUser(User $user, Role $role, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $role, $actor) {
            // create pivot if not exists
            $user->roles()->syncWithoutDetaching([$role->id]);

            // apply role permissions to user_permissions with origin='role'
            $role->loadMissing('permissions');
            foreach ($role->permissions as $permission) {
                $user->userPermissions()->syncWithoutDetaching([
                    $permission->id => [
                        'grant' => (int) $permission->pivot->grant,
                        'origin' => 'role',
                        'origin_id' => $role->id,
                        'applied_by' => $actor?->id ?? null,
                    ],
                ]);
            }

            // invalidate cache and audit
            $this->permissionService->invalidateCache($user);
            $this->auditService->record('role.assign', $actor ?? $user, $user, ['role_id' => $role->id], ['module' => 'roles']);
        });
    }

    /**
     * Revoke a role from a user and remove permissions that originated from that role
     */
    public function revokeRoleFromUser(User $user, Role $role, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $role, $actor) {
            $user->roles()->detach($role->id);

            // remove user_permissions that came from this role
            $user->userPermissions()->newPivotStatement()
                ->where('user_id', $user->id)
                ->where('origin', 'role')
                ->where('origin_id', $role->id)
                ->delete();

            $this->permissionService->invalidateCache($user);
            $this->auditService->record('role.revoke', $actor ?? $user, $user, ['role_id' => $role->id], ['module' => 'roles']);
        });
    }
}
