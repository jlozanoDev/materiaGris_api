<?php

namespace Tests\Unit\Admin\Role;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\Role\GetRoleCommand;
use App\Repositories\Role\RoleRepository;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Exceptions\PermissionDeniedException;

class GetRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithPermission(string $permission = 'admin.role.view'): User
    {
        $user = User::factory()->create();
        $perm = Permission::firstOrCreate(['slug' => $permission], ['name' => $permission]);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
        return $user;
    }

    public function test_execute_throws_permission_denied_when_not_authenticated(): void
    {
        $repo = new RoleRepository();
        $permService = $this->app->make(PermissionService::class);

        $command = new GetRoleCommand($repo, $permService);

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('Unauthorized');

        $command->execute(1);
    }

    public function test_execute_throws_when_role_not_found(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $repo = new RoleRepository();
        $permService = $this->app->make(PermissionService::class);

        $command = new GetRoleCommand($repo, $permService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rol no encontrado');

        $command->execute(999);
    }

    public function test_execute_returns_formatted_role_data(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $role = Role::factory()->create([
            'name' => 'Médico',
            'slug' => 'medico',
            'description' => 'Rol de médico tratante',
            'is_system' => false,
        ]);

        $permission = Permission::create([
            'name' => 'View Patients',
            'slug' => 'getrole.view.patient',
            'action' => 'view',
        ]);

        $role->permissions()->attach($permission->id, ['grant' => 1]);

        $repo = new RoleRepository();
        $permService = $this->app->make(PermissionService::class);

        $command = new GetRoleCommand($repo, $permService);
        $result = $command->execute($role->id);

        $this->assertIsArray($result);
        $this->assertEquals($role->id, $result['id']);
        $this->assertEquals('Médico', $result['name']);
        $this->assertArrayHasKey('permissions', $result);
    }

    public function test_execute_includes_permissions_with_grant(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $perm1 = Permission::create([
            'name' => 'View Patients B',
            'slug' => 'getrole.patientb.view',
            'action' => 'view',
        ]);

        $perm2 = Permission::create([
            'name' => 'Create Reports',
            'slug' => 'getrole.report.create',
            'action' => 'create',
        ]);

        $role->permissions()->attach($perm1->id, ['grant' => 1]);
        $role->permissions()->attach($perm2->id, ['grant' => 0]);

        $repo = new RoleRepository();
        $permService = $this->app->make(PermissionService::class);

        $command = new GetRoleCommand($repo, $permService);
        $result = $command->execute($role->id);

        $this->assertCount(2, $result['permissions']);

        $permissionsArray = $result['permissions']->toArray();
        $slugs = array_column($permissionsArray, 'slug');
        $this->assertContains('getrole.patientb.view', $slugs);
        $this->assertContains('getrole.report.create', $slugs);

        foreach ($result['permissions'] as $p) {
            $this->assertArrayHasKey('id', $p);
            $this->assertArrayHasKey('slug', $p);
            $this->assertArrayHasKey('grant', $p);
            $this->assertIsInt($p['grant']);
        }
    }
}
