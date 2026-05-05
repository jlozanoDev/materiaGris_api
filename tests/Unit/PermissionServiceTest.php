<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_allow_grants_permission(): void
    {
        $perm = Permission::create(['slug' => 'patients.view', 'name' => 'View Patients']);
        $role = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
        $role->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([$role->id]);

        Cache::flush();

        $service = app(PermissionService::class);
        $perms = $service->getEffectivePermissions($user);

        $this->assertArrayHasKey('patients.view', $perms);
        $this->assertSame(1, $perms['patients.view']);
    }

    public function test_deny_overrides_allow(): void
    {
        $perm = Permission::create(['slug' => 'patients.view', 'name' => 'View Patients']);

        $roleAllow = Role::firstOrCreate(['slug' => 'role_allow'], ['name' => 'Allow role']);
        $roleAllow->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $roleDeny = Role::firstOrCreate(['slug' => 'role_deny'], ['name' => 'Deny role']);
        $roleDeny->permissions()->syncWithoutDetaching([$perm->id => ['grant' => -1]]);

        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([$roleAllow->id, $roleDeny->id]);

        Cache::flush();

        $service = app(PermissionService::class);
        $perms = $service->getEffectivePermissions($user);

        $this->assertSame(-1, $perms['patients.view']);
    }

    public function test_user_override_applies(): void
    {
        $perm = Permission::create(['slug' => 'patients.view', 'name' => 'View Patients']);

        $roleAllow = Role::firstOrCreate(['slug' => 'role_allow'], ['name' => 'Allow role']);
        $roleAllow->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([$roleAllow->id]);

        $user->userPermissions()->syncWithoutDetaching([$perm->id => [
            'grant' => -1,
            'origin' => 'user',
            'applied_by' => $user->id,
        ]]);

        Cache::flush();

        $service = app(PermissionService::class);
        $perms = $service->getEffectivePermissions($user);

        $this->assertSame(-1, $perms['patients.view']);
    }

    public function test_invalidate_cache_updates_permissions(): void
    {
        $perm = Permission::create(['slug' => 'patients.view', 'name' => 'View Patients']);
        $role = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
        $role->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $user = User::factory()->create();

        $service = app(PermissionService::class);

        $perms = $service->getEffectivePermissions($user);
        $this->assertArrayNotHasKey('patients.view', $perms);

        $user->roles()->syncWithoutDetaching([$role->id]);
        $service->invalidateCache($user);

        $perms = $service->getEffectivePermissions($user);
        $this->assertSame(1, $perms['patients.view']);
    }
}
