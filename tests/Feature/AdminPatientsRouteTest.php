<?php

namespace Tests\Feature;

use App\Commands\Admin\Patient\GetPatientsCommand;
use App\Services\JwtService;
use App\Models\User;
use App\Models\Permission;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPatientsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pacientes_route_requires_authentication(): void
    {
        $response = $this->getJson('/patients/find');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_admin_pacientes_route_returns_patients_with_valid_jwt(): void
    {
        // Create a real user in the test database and mock token using its id
        $user = User::factory()->create(['email' => 'admin@example.com']);

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
        $perm = Permission::firstOrCreate(['slug' => 'patient.view']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        // Mock GetPatientsCommand to return a list of patients (fields in English)
        $cmdMock = $this->createMock(GetPatientsCommand::class);
        $cmdMock->method('execute')->willReturn(collect([
            [
                'id' => '11111111-1111-1111-1111-111111111111',
                'medical_record_number' => 'NHC001',
                'national_id' => '12345678A',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
            ],
        ]));
        $this->app->instance(GetPatientsCommand::class, $cmdMock);

        $response = $this->getJson('/patients/find', ['Authorization' => 'Bearer token123']);

        $response->assertStatus(200);
        $response->assertExactJson([
            [
                'id' => '11111111-1111-1111-1111-111111111111',
                'medical_record_number' => 'NHC001',
                'national_id' => '12345678A',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
            ],
        ]);
    }
}
