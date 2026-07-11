<?php

namespace Tests\Feature\Admin\Clinic;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Clinic;
use App\Services\JwtService;

class ClinicSettingsTest extends TestCase
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

    private function grantPermission(User $user, string $slug): void
    {
        $perm = \App\Models\Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer token'];
    }

    // ─── GET /admin/clinic ──────────────────────────────────────

    public function test_get_clinic_returns_data(): void
    {
        $clinic = Clinic::factory()->create([
            'nombre' => 'Test Clinic',
            'cuit' => '30-12345678-9',
        ]);

        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->getJson('/admin/clinic', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'nombre' => 'Test Clinic',
            'cuit' => '30-12345678-9',
        ]);
    }

    public function test_get_clinic_requires_auth(): void
    {
        $response = $this->getJson('/admin/clinic');

        $response->assertStatus(401);
    }

    // ─── PUT /admin/clinic ──────────────────────────────────────

    public function test_put_clinic_updates_data(): void
    {
        Clinic::factory()->create([
            'nombre' => 'Original Name',
        ]);

        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.clinic.update');

        $response = $this->putJson('/admin/clinic', [
            'nombre' => 'Updated Clinic',
            'telefono' => '123456789',
            'email' => 'clinic@test.com',
        ], $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'nombre' => 'Updated Clinic',
            'telefono' => '123456789',
            'email' => 'clinic@test.com',
        ]);

        $this->assertDatabaseHas('clinics', [
            'nombre' => 'Updated Clinic',
            'email' => 'clinic@test.com',
        ]);
    }

    public function test_put_clinic_requires_admin(): void
    {
        Clinic::factory()->create();

        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->putJson('/admin/clinic', [
            'nombre' => 'Hacker Name',
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_put_clinic_requires_auth(): void
    {
        $response = $this->putJson('/admin/clinic', [
            'nombre' => 'No Auth',
        ]);

        $response->assertStatus(401);
    }
}
