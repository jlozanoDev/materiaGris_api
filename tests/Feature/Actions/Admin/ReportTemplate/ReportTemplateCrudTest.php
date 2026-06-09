<?php

namespace Tests\Feature\Actions\Admin\ReportTemplate;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ReportTemplate;
use App\Models\PatientReport;
use App\Services\JwtService;

class ReportTemplateCrudTest extends TestCase
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

    public function test_list_returns_paginated_templates(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        ReportTemplate::factory()->count(3)->create();
        ReportTemplate::factory()->create(['is_active' => false]);
        $deleted = ReportTemplate::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/admin/report-templates', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_list_empty_returns_200_with_empty_data(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        $response = $this->getJson('/admin/report-templates', $this->authHeader());

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_list_filter_by_active(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        ReportTemplate::factory()->create(['is_active' => true, 'name' => 'Activo A']);
        ReportTemplate::factory()->create(['is_active' => true, 'name' => 'Activo B']);
        ReportTemplate::factory()->create(['is_active' => false, 'name' => 'Inactivo']);

        $response = $this->getJson('/admin/report-templates?is_active=true', $this->authHeader());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_list_search_by_name(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        ReportTemplate::factory()->create(['name' => 'Informe Radiologico']);
        ReportTemplate::factory()->create(['name' => 'Informe Cardiologico']);
        ReportTemplate::factory()->create(['name' => 'Receta Medica']);

        $response = $this->getJson('/admin/report-templates?q=radiol', $this->authHeader());

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Radiol', $data[0]['name']);
    }

    public function test_create_returns_201_with_created_template(): void
    {
        $this->actingWithPermission('admin.reporttemplate.create');

        $payload = [
            'name' => 'Informe de Alta',
            'description' => 'Informe de alta medica',
            'is_active' => true,
            'structure' => [
                ['label' => 'Diagnostico', 'type' => 'textarea'],
                ['label' => 'Tratamiento', 'type' => 'textarea'],
            ],
        ];

        $response = $this->postJson('/admin/report-templates', $payload, $this->authHeader());

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Informe de Alta']);
        $this->assertDatabaseHas('report_templates', ['name' => 'Informe de Alta']);
        $this->assertNotNull($response->json('id'));
    }

    public function test_create_with_invalid_data_returns_422(): void
    {
        $this->actingWithPermission('admin.reporttemplate.create');

        $response = $this->postJson('/admin/report-templates', [
            'name' => '',
            'structure' => 'not-an-array',
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_create_without_permission_returns_403(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->postJson('/admin/report-templates', [
            'name' => 'Test',
            'structure' => [['label' => 'Test']],
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_create_with_duplicate_name_returns_422(): void
    {
        $this->actingWithPermission('admin.reporttemplate.create');

        ReportTemplate::factory()->create(['name' => 'Informe Unico']);

        $response = $this->postJson('/admin/report-templates', [
            'name' => 'Informe Unico',
            'structure' => [['label' => 'Test']],
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_get_returns_template_by_id(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        $template = ReportTemplate::factory()->create(['name' => 'Template X']);

        $response = $this->getJson("/admin/report-templates/{$template->id}", $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Template X', 'id' => $template->id]);
    }

    public function test_get_nonexistent_returns_404(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        $response = $this->getJson('/admin/report-templates/99999', $this->authHeader());

        $response->assertStatus(404);
    }

    public function test_get_soft_deleted_returns_404(): void
    {
        $this->actingWithPermission('admin.reporttemplate.view');

        $template = ReportTemplate::factory()->create();
        $template->delete();

        $response = $this->getJson("/admin/report-templates/{$template->id}", $this->authHeader());

        $response->assertStatus(404);
    }

    public function test_update_returns_200_with_updated_template(): void
    {
        $this->actingWithPermission('admin.reporttemplate.update');

        $template = ReportTemplate::factory()->create(['name' => 'Original', 'is_active' => true]);

        $response = $this->putJson("/admin/report-templates/{$template->id}", [
            'name' => 'Renombrado',
            'is_active' => false,
        ], $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Renombrado', 'is_active' => false]);
        $this->assertDatabaseHas('report_templates', ['id' => $template->id, 'name' => 'Renombrado']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $this->actingWithPermission('admin.reporttemplate.update');

        $response = $this->putJson('/admin/report-templates/99999', [
            'name' => 'Ghost',
        ], $this->authHeader());

        $response->assertStatus(404);
    }

    public function test_update_without_permission_returns_403(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $template = ReportTemplate::factory()->create();

        $response = $this->putJson("/admin/report-templates/{$template->id}", [
            'name' => 'Attempt',
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_delete_soft_deletes_template(): void
    {
        $this->actingWithPermission('admin.reporttemplate.delete');

        $template = ReportTemplate::factory()->create();

        $response = $this->deleteJson("/admin/report-templates/{$template->id}", [], $this->authHeader());

        $response->assertStatus(204);
        $this->assertSoftDeleted('report_templates', ['id' => $template->id]);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $this->actingWithPermission('admin.reporttemplate.delete');

        $response = $this->deleteJson('/admin/report-templates/99999', [], $this->authHeader());

        $response->assertStatus(404);
    }

    public function test_delete_with_referenced_reports_returns_409(): void
    {
        $this->actingWithPermission('admin.reporttemplate.delete');

        $template = ReportTemplate::factory()->create();
        PatientReport::factory()->create(['template_id' => $template->id]);

        $response = $this->deleteJson("/admin/report-templates/{$template->id}", [], $this->authHeader());

        $response->assertStatus(409);
        $this->assertNotSoftDeleted('report_templates', ['id' => $template->id]);
    }
}
