<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\JwtService;
use App\Commands\Admin\Role\GetPermissionsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_permissions_with_hierarchy_in_description()
    {
        // 1. Setup hierarchy
        $parent = PermissionCategory::create([
            'name' => 'Nivel 1',
            'slug' => 'n1',
            'description' => 'Padre',
            'order' => 1,
        ]);

        $child = PermissionCategory::create([
            'name' => 'Nivel 2',
            'slug' => 'n2',
            'description' => 'Hijo',
            'order' => 1,
            'parent_id' => $parent->id,
        ]);

        $permission = Permission::create([
            'category_id' => $child->id,
            'name' => 'Permiso Prueba',
            'slug' => 'test.permission',
            'action' => 'test',
            'description' => 'Descripción del permiso',
        ]);

        // 2. Setup user and get existing admin role or create one
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        // Mock JWT returning the created user's id
        $token = new class($user->id) {
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

        // 3. Request
        $response = $this->withHeaders(['Authorization' => 'Bearer fake-token'])
            ->getJson('/admin/permissions');

        // 4. Verify
        $response->assertStatus(200);
        $data = $response->json();

        // Find the test permission in results
        $p = collect($data)->firstWhere('slug', 'test.permission');
        
        $this->assertNotNull($p);
        $this->assertEquals('Nivel 1 > Nivel 2', $p['category']);
        $this->assertEquals('Nivel 1 > Nivel 2: Descripción del permiso', $p['description']);
    }
}
