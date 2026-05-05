<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\RoleAssignmentService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class RoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_role_applies_user_permissions_with_origin_role(): void
    {
        $perm = Permission::firstOrCreate(['slug' => 'admin.user.view']);
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $role->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $user = User::factory()->create();
        $actor = User::factory()->create();

        $service = app(RoleAssignmentService::class);
        $service->assignRoleToUser($user, $role, $actor);

        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $role->id]);
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'origin' => 'role',
            'origin_id' => $role->id,
        ]);
    }

    public function test_revoke_role_removes_origin_role_user_permissions(): void
    {
        $perm = Permission::firstOrCreate(['slug' => 'admin.user.view']);
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $role->permissions()->syncWithoutDetaching([$perm->id => ['grant' => 1]]);

        $user = User::factory()->create();
        $actor = User::factory()->create();

        $service = app(RoleAssignmentService::class);
        $service->assignRoleToUser($user, $role, $actor);

        // ensure applied
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'origin' => 'role',
            'origin_id' => $role->id,
        ]);

        $service->revokeRoleFromUser($user, $role, $actor);

        $this->assertDatabaseMissing('user_permissions', [
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'origin' => 'role',
            'origin_id' => $role->id,
        ]);
    }
}
