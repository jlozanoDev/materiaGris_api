<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\UpdateUserCommand;
use App\Repositories\User\GetUserRepository;
use App\Repositories\User\SaveUserRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\Permission\GetPermissionRepository;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\PermissionService;
use App\Services\RoleAssignmentService;
use App\Services\AuditService;

class UpdateUserCommandTest extends TestCase
{
    use RefreshDatabase;
    protected function getCommand(User $actor): UpdateUserCommand
    {
        $leer = $this->app->make(GetUserRepository::class);
        $escribir = $this->app->make(SaveUserRepository::class);
        $permissionService = $this->app->make(PermissionService::class);
        $roleAssignmentService = $this->app->make(RoleAssignmentService::class);
        $auditService = $this->app->make(AuditService::class);
        $roleRepository = $this->app->make(RoleRepository::class);
        $permissionRepository = $this->app->make(GetPermissionRepository::class);

        return new UpdateUserCommand(
            $leer,
            $escribir,
            $permissionService,
            $roleAssignmentService,
            $auditService,
            $roleRepository,
            $permissionRepository,
        );
    }

    private function setupActorWithPermissions(): User
    {
        // Buscar o crear permission existente
        $permission = Permission::firstOrCreate(
            ['slug' => 'admin.user.update'],
            ['name' => 'Update Users', 'action' => 'update']
        );

        // Crear rol de sistema
        $role = Role::firstOrCreate(
            ['slug' => 'system-admin'],
            ['name' => 'System Admin', 'is_system' => true]
        );

        // Crear actor
        $actor = User::factory()->create();
        $actor->roles()->sync([$role->id]);
        $actor->userPermissions()->syncWithoutDetaching([
            $permission->id => [
                'grant' => 1, 
                'origin' => 'user', 
                'origin_id' => null, 
                'applied_by' => $actor->id
            ]
        ]);

        return $actor;
    }

    public function test_update_user_basic_fields(): void
    {
        $user = User::factory()->create();
        $actor = $this->setupActorWithPermissions();
        $this->actingAs($actor);

        $command = $this->getCommand($actor);
        $result = $command->execute($user->id, ['name' => 'Nuevo Nombre']);

        $this->assertIsArray($result);
        $this->assertEquals('Nuevo Nombre', $result['name']);
    }

    public function test_assign_role_to_user(): void
    {
        $user = User::factory()->create();
        
        // Crear rol nuevo con permissions
        $newRole = Role::firstOrCreate(
            ['slug' => 'medico-test'],
            ['name' => 'Médico Test', 'is_system' => false]
        );
        
        $permission = Permission::firstOrCreate(
            ['slug' => 'test.perm'],
            ['name' => 'Test Permission']
        );
        $newRole->permissions()->syncWithoutDetaching([$permission->id => ['grant' => 1]]);

        $actor = $this->setupActorWithPermissions();
        $this->actingAs($actor);

        $command = $this->getCommand($actor);
        $result = $command->execute($user->id, ['roles' => [$newRole->id]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result['roles']);
    }

    public function test_revoke_role_from_user(): void
    {
        $user = User::factory()->create();
        
        $role = Role::firstOrCreate(
            ['slug' => 'enfermera-test'],
            ['name' => 'Enfermera Test', 'is_system' => false]
        );
        $user->roles()->sync([$role->id]);

        $actor = $this->setupActorWithPermissions();
        $this->actingAs($actor);

        $command = $this->getCommand($actor);
        $result = $command->execute($user->id, ['roles_remove' => [$role->id]]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result['roles']);
    }

    public function test_add_permission_override(): void
    {
        $user = User::factory()->create();
        $actor = $this->setupActorWithPermissions();
        $this->actingAs($actor);

        $permission = Permission::firstOrCreate(
            ['slug' => 'patients.edit.test'],
            ['name' => 'Patients Edit Test']
        );

        $command = $this->getCommand($actor);
        $result = $command->execute($user->id, [
            'permissions' => [
                ['permission_id' => $permission->id, 'grant' => 1]
            ]
        ]);

        $this->assertIsArray($result);
        $found = false;
        foreach ($result['user_permissions'] as $perm) {
            if ($perm['permission_id'] == $permission->id) {
                $found = true;
                $this->assertEquals(1, $perm['grant']);
                $this->assertEquals('user', $perm['origin']);
            }
        }
        $this->assertTrue($found);
    }
}