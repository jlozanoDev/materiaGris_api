<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\JwtService;
use Illuminate\Support\Facades\Cache;

class AdminRolesCrudTest extends TestCase
{
    use RefreshDatabase;

    private function mockJwtForUserId(int $id)
    {
        $token = new class($id) {
            private $id;
            public function __construct($id) { $this->id = $id; }
            public function claims() {
                $id = $this->id;
                return new class($id) {
                    private $id;
                    public function __construct($id) { $this->id = $id; }
                    public function get($key) { return $key === 'sub' ? $this->id : null; }
                };
            }
        };

        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);
    }

    private function setupUserWithPermission(string $slug)
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'action' => 'test']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
        return $user;
    }

    public function test_can_list_roles()
    {
        $this->setupUserWithPermission('admin.role.view');
        Role::factory()->create(['name' => 'Role A']);
        Role::factory()->create(['name' => 'Role B']);

        $response = $this->getJson('/admin/roles', ['Authorization' => 'Bearer token']);
        
        $response->assertStatus(200);
        $totalRoles = Role::count();
        $response->assertJsonCount($totalRoles);
    }

    public function test_can_create_role_with_permissions()
    {
        $this->setupUserWithPermission('admin.role.create');
        $perm = Permission::firstOrCreate(['slug' => 'test.perm'], ['name' => 'test.perm', 'action' => 'test']);

        $payload = [
            'name' => 'New Role',
            'description' => 'Desc',
            'permissions' => [
                ['id' => $perm->id, 'grant' => 1]
            ]
        ];

        $response = $this->postJson('/admin/roles', $payload, ['Authorization' => 'Bearer token']);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['name' => 'New Role']);
        
        $role = Role::where('name', 'New Role')->first();
        $this->assertCount(1, $role->permissions);
        $this->assertEquals(1, $role->permissions->first()->pivot->grant);

        // Verify audit
        $this->assertDatabaseHas('audits', [
            'type' => 'role.create',
            'target_id' => $role->id,
            'target_type' => Role::class
        ]);
    }

    public function test_can_update_role_and_invalidates_cache()
    {
        $this->setupUserWithPermission('admin.role.update');
        $role = Role::factory()->create(['name' => 'Old Name']);
        $userWithRole = User::factory()->create();
        $userWithRole->roles()->attach($role->id);

        // Pre-fill cache
        $cacheKey = "user_permissions_{$userWithRole->id}";
        Cache::put($cacheKey, ['some' => 'data']);

        $perm = Permission::factory()->create(['slug' => 'p1']);
        
        $payload = [
            'name' => 'Updated Name',
            'permissions' => [
                ['id' => $perm->id, 'grant' => -1]
            ]
        ];

        $response = $this->putJson("/admin/roles/{$role->id}", $payload, ['Authorization' => 'Bearer token']);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Updated Name']);
        
        // Verify cache invalidation
        $this->assertFalse(Cache::has($cacheKey));

        // Verify audit diff
        $this->assertDatabaseHas('audits', [
            'type' => 'role.update',
            'target_id' => $role->id
        ]);
    }

    public function test_cannot_delete_system_role()
    {
        $this->setupUserWithPermission('admin.role.delete');
        $role = Role::factory()->create(['name' => 'System', 'is_system' => true]);

        $response = $this->deleteJson("/admin/roles/{$role->id}", [], ['Authorization' => 'Bearer token']);
        
        $response->assertStatus(403);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_can_delete_regular_role()
    {
        $this->setupUserWithPermission('admin.role.delete');
        $role = Role::factory()->create(['name' => 'Regular', 'is_system' => false]);

        $response = $this->deleteJson("/admin/roles/{$role->id}", [], ['Authorization' => 'Bearer token']);
        
        $response->assertStatus(204);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        // Verify audit
        $this->assertDatabaseHas('audits', [
            'type' => 'role.delete'
        ]);
    }
}
