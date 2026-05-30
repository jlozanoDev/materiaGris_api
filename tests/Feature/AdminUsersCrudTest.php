<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\JwtService;

class AdminUsersCrudTest extends TestCase
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

    public function test_create_requires_authentication()
    {
        $response = $this->postJson('/admin/users', ['name' => 'X', 'email' => 'x@example.com']);
        $response->assertStatus(401);
    }

    public function test_create_requires_permission()
    {
        $actor = User::factory()->create();
        $this->mockJwtForUserId($actor->id);

        $response = $this->postJson('/admin/users', ['name' => 'X', 'email' => 'x@example.com'], ['Authorization' => 'Bearer token']);
        $response->assertStatus(403);
    }

    public function test_create_user_when_allowed()
    {
        $actor = User::factory()->create();
        $this->mockJwtForUserId($actor->id);

        $perm = \App\Models\Permission::firstOrCreate(['slug' => 'admin.user.create'], ['name' => 'Create users']);
        $actor->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $response = $this->postJson('/admin/users', ['name' => 'New', 'email' => 'new@example.com'], ['Authorization' => 'Bearer t']);
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'name' => 'New']);
    }

    public function test_update_user_changes_values_when_allowed()
    {
        $actor = User::factory()->create();
        $this->mockJwtForUserId($actor->id);
        $perm = \App\Models\Permission::firstOrCreate(['slug' => 'admin.user.update'], ['name' => 'Update users']);
        $actor->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $target = User::factory()->create(['name' => 'Before', 'email' => 'target@example.com']);

        $response = $this->putJson("/admin/users/{$target->id}", ['name' => 'After'], ['Authorization' => 'Bearer t']);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'After']);
    }

    public function test_delete_soft_deletes_user_when_allowed()
    {
        $actor = User::factory()->create();
        $this->mockJwtForUserId($actor->id);
        $perm = \App\Models\Permission::firstOrCreate(['slug' => 'admin.user.delete'], ['name' => 'Delete users']);
        $actor->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $target = User::factory()->create();

        $response = $this->deleteJson("/admin/users/{$target->id}", [], ['Authorization' => 'Bearer t']);
        $response->assertStatus(204);

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
