<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\JwtService;
use App\Models\User;
use App\Models\Patient;
use App\Models\Permission;

class PatientsCrudTest extends TestCase
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

    public function test_create_patient_returns_201_and_db_has_patient(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $token = $this->fakeToken($user->id);
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);
        
        // Give permission
        $perm = Permission::firstOrCreate(['slug' => 'patient.create']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $payload = [
            'medical_record_number' => '1234',
            'national_id' => '12345678A',
            'first_name' => 'Jorge',
            'last_name' => 'Lozano',
            'second_last_name' => 'Fortes',
            'gender' => 'M',
            'date_of_birth' => '1980-02-11',
            'city' => 'Ciudad',
            'insurance_id' => 1234,
            'is_active' => true,
        ];

        $response = $this->postJson('/patients', $payload, ['Authorization' => 'Bearer token123']);

        $response->assertStatus(201);
        $response->assertJsonFragment(['medical_record_number' => '1234', 'national_id' => '12345678A', 'first_name' => 'Jorge']);
        $this->assertDatabaseHas('patients', ['national_id' => '12345678A', 'first_name' => 'Jorge']);
        $this->assertTrue(is_int($response->json('id')) || is_numeric($response->json('id')));
    }

    public function test_update_patient_allows_same_values_and_blocks_duplicates(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $token = $this->fakeToken($user->id);
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willReturn($token);
        $this->app->instance(JwtService::class, $jwtMock);

        // Give permission
        $perm = Permission::firstOrCreate(['slug' => 'patient.update']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $patientA = Patient::create([
            'medical_record_number' => 'A123',
            'national_id' => 'DNI-A',
            'first_name' => 'Alice',
            'last_name' => 'A',
            'is_active' => true,
        ]);

        $patientB = Patient::create([
            'medical_record_number' => 'B456',
            'national_id' => 'DNI-B',
            'first_name' => 'Bob',
            'last_name' => 'B',
            'is_active' => true,
        ]);

        // Update patientA with same national_id (should succeed)
        $payload = [
            'medical_record_number' => 'A123',
            'national_id' => 'DNI-A',
            'first_name' => 'AliceUpdated',
            'last_name' => 'A',
            'is_active' => true,
        ];

        $response = $this->putJson("/patients/{$patientA->id}", $payload, ['Authorization' => 'Bearer token123']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['first_name' => 'AliceUpdated']);

        // Try to set national_id to one already used by patientB (should fail)
        $payload['national_id'] = 'DNI-B';
        $response = $this->putJson("/patients/{$patientA->id}", $payload, ['Authorization' => 'Bearer token123']);
        $response->assertStatus(422);
        $response->assertJson(['message' => 'El dni ya existe.']);

        // Try to set medical_record_number to one used by patientB (should fail)
        $payload['national_id'] = 'DNI-A';
        $payload['medical_record_number'] = 'B456';
        $response = $this->putJson("/patients/{$patientA->id}", $payload, ['Authorization' => 'Bearer token123']);
        $response->assertStatus(422);
        $response->assertJson(['message' => 'El número de historial médico ya existe.']);
    }
}
