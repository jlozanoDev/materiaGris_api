<?php

namespace Tests\Feature;

use App\Commands\Admin\User\GetUsersCommand;
use App\Services\JwtService;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUsersRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_route_requires_authentication(): void
    {
        $response = $this->getJson('/admin/users');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_admin_users_route_returns_users_with_valid_jwt(): void
    {
        // Create a real user in the test database
        $user = User::factory()->create(['email' => 'admin@example.com']);

        // Prepare a fake token object that returns the created user's id
        $token = new class($user->id) {
            private $id;
            public function __construct($id) { $this->id = $id; }
            public function claims()
            {
                $id = $this->id;
                return new class($id) {
                    private $id;
                    public function __construct($id) { $this->id = $id; }
                    public function get($key)
                    {
                        if ($key === 'sub') {
                            return $this->id;
                        }
                        return null;
                    }
                };
            }
        };

        // Mock JwtService to accept any token and return our fake token
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        // Give permission to user
        $perm = \App\Models\Permission::firstOrCreate(['slug' => 'admin.user.view'], ['name' => 'View users']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        // Mock GetUsersCommand to return a collection of users
        $cmdMock = $this->createMock(GetUsersCommand::class);
        $cmdMock->method('execute')->willReturn(collect([
            ['id' => $user->id, 'email' => 'admin@example.com'],
        ]));
        $this->app->instance(GetUsersCommand::class, $cmdMock);

        $response = $this->getJson('/admin/users', ['Authorization' => 'Bearer token123']);

        $response->assertStatus(200);
        $response->assertExactJson([
            ['id' => $user->id, 'email' => 'admin@example.com'],
        ]);
    }
}
