<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Auth\MeCommand;
use App\Services\JwtService;
use App\Models\User;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/auth/me');
        $response->assertStatus(401);
    }

    public function test_me_returns_user_payload_when_authenticated(): void
    {
        $user = User::factory()->create(['email' => 'u@example.com']);

        $token = new class($user->id) {
            private $id;
            public function __construct($id) { $this->id = $id; }
            public function claims() {
                $id = $this->id;
                return new class($id) {
                    private $id;
                    public function __construct($id) { $this->id = $id; }
                    public function get($k) {
                        if ($k === 'sub') return $this->id;
                        return null;
                    }
                };
            }
        };

        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        $expected = [
            'id' => 1,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => ['admin'],
            'permissions' => ['admin.user.view' => 1],
            'permissions_version' => now()->toIso8601String(),
        ];

        $cmdMock = $this->createMock(MeCommand::class);
        $cmdMock->method('execute')->with($user->id)->willReturn($expected);
        $this->app->instance(MeCommand::class, $cmdMock);

        $response = $this->getJson('/auth/me', ['Authorization' => 'Bearer token123']);

        $response->assertStatus(200);
        $response->assertExactJson($expected);
    }
}
