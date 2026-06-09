<?php

namespace Tests\Feature\Commands\Admin\TipoInforme;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ReportTemplate;
use App\Models\PatientReport;
use App\Services\JwtService;
use App\Exceptions\PermissionDeniedException;

class TipoInformeCommandTest extends TestCase
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

    // ─── LIST COMMAND ──────────────────────────────────────

    public function test_list_command_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        // No permission granted

        $response = $this->getJson('/admin/tipos-informe', $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_list_command_requires_authentication(): void
    {
        $response = $this->getJson('/admin/tipos-informe');

        $response->assertStatus(401);
    }

    // ─── CREATE COMMAND ────────────────────────────────────

    public function test_create_command_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->postJson('/admin/tipos-informe', [
            'name' => 'Test',
            'structure' => [['label' => 'Test']],
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_create_command_error_propagation(): void
    {
        // Permission granted, but duplicate name should propagate from repo
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.tipoinforme.create');

        ReportTemplate::factory()->create(['name' => 'Duplicado']);

        $response = $this->postJson('/admin/tipos-informe', [
            'name' => 'Duplicado',
            'structure' => [['label' => 'Test']],
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    // ─── GET COMMAND ───────────────────────────────────────

    public function test_get_command_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $template = ReportTemplate::factory()->create();

        $response = $this->getJson("/admin/tipos-informe/{$template->id}", $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_get_command_not_found_propagation(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.tipoinforme.view');

        $response = $this->getJson('/admin/tipos-informe/99999', $this->authHeader());

        $response->assertStatus(404);
    }

    // ─── UPDATE COMMAND ────────────────────────────────────

    public function test_update_command_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $template = ReportTemplate::factory()->create();

        $response = $this->putJson("/admin/tipos-informe/{$template->id}", [
            'name' => 'New Name',
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_update_command_not_found_propagation(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.tipoinforme.update');

        $response = $this->putJson('/admin/tipos-informe/99999', [
            'name' => 'Ghost',
        ], $this->authHeader());

        $response->assertStatus(404);
    }

    // ─── DELETE COMMAND ────────────────────────────────────

    public function test_delete_command_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $template = ReportTemplate::factory()->create();

        $response = $this->deleteJson("/admin/tipos-informe/{$template->id}", [], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_delete_command_referenced_reports_block(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.tipoinforme.delete');

        $template = ReportTemplate::factory()->create();
        PatientReport::factory()->create(['template_id' => $template->id]);

        $response = $this->deleteJson("/admin/tipos-informe/{$template->id}", [], $this->authHeader());

        $response->assertStatus(409);
    }

    public function test_delete_command_not_found_propagation(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.tipoinforme.delete');

        $response = $this->deleteJson('/admin/tipos-informe/99999', [], $this->authHeader());

        $response->assertStatus(404);
    }

    // ─── UNAUTHENTICATED ───────────────────────────────────

    public function test_list_requires_authentication(): void
    {
        $response = $this->getJson('/admin/tipos-informe');
        $response->assertStatus(401);
    }

    public function test_create_requires_authentication(): void
    {
        $response = $this->postJson('/admin/tipos-informe', ['name' => 'X', 'structure' => []]);
        $response->assertStatus(401);
    }

    public function test_get_requires_authentication(): void
    {
        $response = $this->getJson('/admin/tipos-informe/1');
        $response->assertStatus(401);
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->putJson('/admin/tipos-informe/1', ['name' => 'X']);
        $response->assertStatus(401);
    }

    public function test_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/admin/tipos-informe/1');
        $response->assertStatus(401);
    }
}
