<?php

namespace App\Services;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Return an array map of permission slug => grant (1 = allow, -1 = deny, 0 = neutral)
     *
     * @param User $user
     * @return array<string,int>
     */
    public function getEffectivePermissions(User $user): array
    {
        // Try cache first (short-lived)
        $cacheKey = "user_permissions_{$user->id}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = [];

        // Always reload roles/permissions to reflect recent changes
        $user->load('roles.permissions');
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $slug = $permission->slug;
                $grant = (int) $permission->pivot->grant;

                // Deny overrides everything
                if (!isset($result[$slug])) {
                    $result[$slug] = 0;
                }

                if ($grant === -1) {
                    $result[$slug] = -1;
                } elseif ($grant === 1 && $result[$slug] !== -1) {
                    // only set allow if not denied
                    $result[$slug] = 1;
                }
            }
        }

        // Apply user-specific overrides (reload to ensure fresh state)
        $user->load('userPermissions');
        foreach ($user->userPermissions as $perm) {
            $slug = $perm->slug;
            $grant = (int) $perm->pivot->grant;

            if ($grant === -1) {
                $result[$slug] = -1;
            } elseif ($grant === 1 && ($result[$slug] ?? 0) !== -1) {
                $result[$slug] = 1;
            }
        }

        // Cache result for short time
        Cache::put($cacheKey, $result, 60);

        return $result;
    }

    /**
     * Check if a user has a permission (effective)
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $perms = $this->getEffectivePermissions($user);
        return isset($perms[$permission]) && $perms[$permission] === 1;
    }

    /**
     * Ensure user has the required permissions, otherwise throw PermissionDeniedException
     * @param User $user
     * @param array|string $permissions
     * @param string $mode 'any'|'all'
     * @throws PermissionDeniedException
     */
    public function ensure(User $user, $permissions, string $mode = 'any'): void
    {
        $perms = is_array($permissions) ? $permissions : [$permissions];
        if ($mode === 'all') {
            foreach ($perms as $p) {
                if (! $this->userHasPermission($user, $p)) {
                    throw new PermissionDeniedException("User lacks required permission: {$p}");
                }
            }
            return;
        }

        // any
        foreach ($perms as $p) {
            if ($this->userHasPermission($user, $p)) {
                return;
            }
        }

        throw new PermissionDeniedException('User lacks required permissions');
    }

    /**
     * Invalidate cache for a user's computed permissions
     */
    public function invalidateCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
    }
}
