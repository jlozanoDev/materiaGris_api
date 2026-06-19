<?php

namespace Tests\Feature\Actions\Reports;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ReportTemplate;
use App\Services\JwtService;

class GetActiveTemplatesTest extends TestCase
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

    private function actingWithPermission(string $slug): User
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, $slug);
        return $user;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer token'];
    }

    public function test_authenticated_professional_can_retrieve_active_templates(): void
    {
        $this->actingWithPermission('report.create');

        $active1 = ReportTemplate::factory()->create([
            'name' => 'Plantilla B',
            'is_active' => true,
            'description' => 'Descripción B',
        ]);
        $active2 = ReportTemplate::factory()->create([
            'name' => 'Plantilla A',
            'is_active' => true,
            'description' => 'Descripción A',
        ]);
        ReportTemplate::factory()->create([
            'name' => 'Inactiva',
            'is_active' => false,
        ]);
        $deleted = ReportTemplate::factory()->create([
            'name' => 'Eliminada',
            'is_active' => true,
        ]);
        $deleted->delete();

        $response = $this->getJson('/templates/active', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'is_active', 'structure', 'created_at', 'updated_at'],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Assert alphabetical order by name
        $this->assertEquals('Plantilla A', $data[0]['name']);
        $this->assertEquals('Plantilla B', $data[1]['name']);

        // Assert only active templates returned
        $names = array_column($data, 'name');
        $this->assertNotContains('Inactiva', $names);
        $this->assertNotContains('Eliminada', $names);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/templates/active');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_invalid_token_returns_401(): void
    {
        $jwtMock = $this->createMock(JwtService::class);
        $jwtMock->method('parseAndValidate')->willThrowException(new \RuntimeException('Token expired'));
        $this->app->instance(JwtService::class, $jwtMock);

        $response = $this->getJson('/templates/active', ['Authorization' => 'Bearer invalid_token_here']);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid token']);
    }

    public function test_user_without_report_create_permission_returns_403(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->getJson('/templates/active', $this->authHeader());

        $response->assertStatus(403);
        $response->assertJson(['message' => 'User lacks required permissions']);
    }

    public function test_unexpected_exception_returns_500(): void
    {
        $this->actingWithPermission('report.create');

        $repoMock = $this->createMock(\App\Repositories\ReportTemplate\ReportTemplateReadRepository::class);
        $repoMock->method('listarActivas')->willThrowException(new \RuntimeException('DB connection lost'));
        $this->app->instance(\App\Repositories\ReportTemplate\ReportTemplateReadRepository::class, $repoMock);

        $response = $this->getJson('/templates/active', $this->authHeader());

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Internal server error']);
    }

    public function test_no_active_templates_returns_empty_array_with_200(): void
    {
        $this->actingWithPermission('report.create');

        ReportTemplate::factory()->create(['is_active' => false, 'name' => 'Unica Inactiva']);

        $response = $this->getJson('/templates/active', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }
}
