<?php

namespace Tests\Feature\Patients;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\JwtService;
use App\Models\User;
use App\Models\Patient;
use App\Models\Permission;

class GetPatientTest extends TestCase
{
    use RefreshDatabase;

    private function fakeToken($id)
    {
        return new class($id) {
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
    }

    public function test_can_retrieve_patient_by_id(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $token = $this->fakeToken($user->id);
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        // Grant patient.view permission
        $perm = Permission::firstOrCreate(['slug' => 'patient.view']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $patient = Patient::create([
            'medical_record_number' => 'MR-001',
            'national_id' => 'DNI-12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'second_last_name' => 'García',
            'gender' => 'M',
            'date_of_birth' => '1985-06-15',
            'city' => 'Madrid',
            'insurance_id' => 1001,
            'is_active' => true,
            'last_visit_at' => '2026-05-20 14:30:00',
            'email' => 'juan@example.com',
            'phone' => '912345678',
            'mobile' => '612345678',
            'contact_name' => 'María García',
            'contact_phone' => '698765432',
            'address_line1' => 'Calle Mayor 10',
            'address_line2' => 'Piso 3A',
            'neighborhood' => 'Centro',
            'postal_code' => '28001',
            'state' => 'Comunidad de Madrid',
            'country' => 'España',
        ]);

        $response = $this->getJson("/patients/{$patient->id}", [
            'Authorization' => 'Bearer token123',
        ]);

        $response->assertStatus(200);

        // Verify the complete patient record is returned as a direct JSON object
        $response->assertJsonFragment([
            'id' => $patient->id,
            'medical_record_number' => 'MR-001',
            'national_id' => 'DNI-12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'second_last_name' => 'García',
            'gender' => 'M',
            'date_of_birth' => '1985-06-15',
            'city' => 'Madrid',
            'insurance_id' => 1001,
            'is_active' => true,
            'last_visit_at' => '2026-05-20',
            'email' => 'juan@example.com',
            'phone' => '912345678',
            'mobile' => '612345678',
            'contact_name' => 'María García',
            'contact_phone' => '698765432',
            'address_line1' => 'Calle Mayor 10',
            'address_line2' => 'Piso 3A',
            'neighborhood' => 'Centro',
            'postal_code' => '28001',
            'state' => 'Comunidad de Madrid',
            'country' => 'España',
            'full_name' => 'Juan Pérez García',
        ]);

        // Verify no data wrapper — response is a direct object, not wrapped in a 'data' key
        $response->assertJsonMissing(['data' => $response->json()]);

        // Verify age is computed as an integer
        $response->assertJsonStructure(['age']);
        $this->assertIsInt($response->json('age'));
        $this->assertGreaterThanOrEqual(40, $response->json('age'));
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/patients/1');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_returns_403_when_lacks_permission(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $token = $this->fakeToken($user->id);
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        // Create a patient so the record exists
        $patient = Patient::factory()->create();

        // Do NOT grant patient.view — expect 403 from require_permissions middleware
        $response = $this->getJson("/patients/{$patient->id}", [
            'Authorization' => 'Bearer token123',
        ]);

        $response->assertStatus(403);
    }

    public function test_returns_404_when_patient_not_found(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $token = $this->fakeToken($user->id);
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        // Grant patient.view permission
        $perm = Permission::firstOrCreate(['slug' => 'patient.view']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $response = $this->getJson('/patients/99999', [
            'Authorization' => 'Bearer token123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Paciente no encontrado']);
    }
}
