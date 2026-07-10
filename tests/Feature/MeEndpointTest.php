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

    private function mockJwtForUserId(int $id): void
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
            'roles' => [['id' => 1, 'name' => 'Admin']],
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

    public function test_me_includes_professional_fields(): void
    {
        $user = User::factory()->create([
            'apellido' => 'García',
            'num_colegiado' => '12345',
            'especialidad' => 'Cardiología',
            'telefono' => '987654321',
        ]);

        $this->mockJwtForUserId($user->id);

        $response = $this->getJson('/auth/me', ['Authorization' => 'Bearer token']);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'apellido' => 'García',
            'num_colegiado' => '12345',
            'especialidad' => 'Cardiología',
            'telefono' => '987654321',
        ]);
    }
}
