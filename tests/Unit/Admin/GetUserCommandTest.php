<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\GetUserCommand;
use App\Repositories\User\GetUserRepository;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Permission;

class GetUserCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithPermission(string $permission = 'admin.user.view'): User
    {
        $user = User::factory()->create();
        $perm = Permission::firstOrCreate(['slug' => $permission], ['name' => $permission]);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
        return $user;
    }

    public function test_execute_returns_null_when_user_not_found(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $repo = new GetUserRepository();
        $permissionService = $this->app->make(PermissionService::class);

        $command = new GetUserCommand($repo, $permissionService);
        $result = $command->execute(999);

        $this->assertNull($result);
    }

    public function test_execute_returns_formatted_user_with_roles_and_permissions(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $role = \App\Models\Role::factory()->create();
        $permission = Permission::factory()->create();
        
        // Asignar rol al usuario (sin grant en pivot - el grant viene de role_permissions)
        $user->roles()->attach($role->id);
        
        // Permiso override individual
        $user->userPermissions()->attach($permission->id, [
            'grant' => 1,
            'origin' => 'user',
            'origin_id' => null,
            'applied_by' => $user->id,
        ]);

        $repo = new GetUserRepository();
        $permissionService = $this->app->make(PermissionService::class);

        $command = new GetUserCommand($repo, $permissionService);
        $result = $command->execute($user->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('active', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('user_permissions', $result);
        $this->assertArrayHasKey('effective_permissions', $result);

        $this->assertIsArray($result['roles']);
        $this->assertIsArray($result['user_permissions']);
        $this->assertIsArray($result['effective_permissions']);
    }

    public function test_execute_includes_role_details(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $role = \App\Models\Role::factory()->create([
            'name' => 'Médico',
            'slug' => 'medico',
            'is_system' => false,
        ]);
        
        $user->roles()->attach($role->id);

        $repo = new GetUserRepository();
        $permissionService = $this->app->make(PermissionService::class);

        $command = new GetUserCommand($repo, $permissionService);
        $result = $command->execute($user->id);

        $this->assertCount(1, $result['roles']);
        $this->assertEquals('Médico', $result['roles'][0]['name']);
        $this->assertEquals('medico', $result['roles'][0]['slug']);
        $this->assertFalse($result['roles'][0]['is_system']);
    }

    public function test_execute_includes_user_permission_details(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $permission = \App\Models\Permission::factory()->create([
            'slug' => 'patients.view',
        ]);
        
        $user->userPermissions()->attach($permission->id, [
            'grant' => 1,
            'origin' => 'user',
            'origin_id' => null,
            'applied_by' => $user->id,
        ]);

        $repo = new GetUserRepository();
        $permissionService = $this->app->make(PermissionService::class);

        $command = new GetUserCommand($repo, $permissionService);
        $result = $command->execute($user->id);

        $slugs = array_column($result['user_permissions'], 'slug');
        $this->assertContains('patients.view', $slugs);
    }
}