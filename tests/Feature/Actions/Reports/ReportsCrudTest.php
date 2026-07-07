<?php

namespace Tests\Feature\Actions\Reports;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Patient;
use App\Models\ReportTemplate;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use App\Services\JwtService;
use Carbon\Carbon;

class ReportsCrudTest extends TestCase
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

    private function actingWithPermissions(array $slugs): User
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        foreach ($slugs as $slug) {
            $this->grantPermission($user, $slug);
        }
        return $user;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer token'];
    }

    // ─── INIT ────────────────────────────────────────────────

    public function test_init_report_creates_draft_with_snapshot(): void
    {
        $this->actingWithPermission('report.create');

        $patient = Patient::factory()->create();
        $template = ReportTemplate::factory()->create([
            'is_active' => true,
            'structure' => ['sections' => [['title' => 'Sección 1', 'rows' => []]]],
        ]);

        $response = $this->postJson('/reports', [
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ], $this->authHeader());

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'patient_id', 'user_id', 'template_id', 'status',
            'template_structure_snapshot', 'values', 'created_at', 'updated_at',
        ]);
        $response->assertJsonFragment([
            'status' => 'draft',
            'patient_id' => $patient->id,
        ]);

        $this->assertDatabaseHas('patient_reports', [
            'patient_id' => $patient->id,
            'status' => 'draft',
        ]);
    }

    public function test_init_requires_view_permission(): void
    {
        // Without permission, should get 403
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $patient = Patient::factory()->create();
        $template = ReportTemplate::factory()->create();

        $response = $this->postJson('/reports', [
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_init_validates_required_fields(): void
    {
        $this->actingWithPermission('report.create');

        $response = $this->postJson('/reports', [], $this->authHeader());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['patient_id', 'template_id']);
    }

    // ─── LIST ────────────────────────────────────────────────

    public function test_list_returns_paginated_reports(): void
    {
        $this->actingWithPermission('report.view');

        $template = ReportTemplate::factory()->create();
        PatientReport::factory()->count(3)->create(['template_id' => $template->id]);
        PatientReport::factory()->signed()->create(['template_id' => $template->id]);

        $response = $this->getJson('/reports', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'patient_name', 'author_name', 'template_name', 'status', 'createdAt', 'updatedAt']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_list_filters_by_status(): void
    {
        $this->actingWithPermission('report.view');

        $template = ReportTemplate::factory()->create();
        PatientReport::factory()->create(['template_id' => $template->id]); // draft
        PatientReport::factory()->signed()->create(['template_id' => $template->id]);

        $response = $this->getJson('/reports?status=signed', $this->authHeader());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('signed', $response->json('data.0.status'));
    }

    public function test_list_filters_by_patient_id(): void
    {
        $this->actingWithPermission('report.view');

        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        PatientReport::factory()->create(['patient_id' => $patient1->id]);
        PatientReport::factory()->create(['patient_id' => $patient1->id]);
        PatientReport::factory()->create(['patient_id' => $patient2->id]);

        $response = $this->getJson("/reports?patient_id={$patient1->id}", $this->authHeader());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_list_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->getJson('/reports', $this->authHeader());

        $response->assertStatus(403);
    }

    // ─── GET ─────────────────────────────────────────────────

    public function test_get_returns_report_with_relations(): void
    {
        $this->actingWithPermission('report.view');

        $report = PatientReport::factory()->create([
            'values' => ['diagnostico' => 'Test value'],
        ]);

        $response = $this->getJson("/reports/{$report->id}", $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $report->id,
            'status' => 'draft',
        ]);
        $this->assertEquals('Test value', $response->json('values.diagnostico'));
    }

    public function test_get_not_found_returns_404(): void
    {
        $this->actingWithPermission('report.view');

        $response = $this->getJson('/reports/99999', $this->authHeader());

        $response->assertStatus(404);
    }

    // ─── SAVE DRAFT ──────────────────────────────────────────

    public function test_save_draft_updates_values(): void
    {
        $user = $this->actingWithPermissions(['report.edit', 'report.create']);

        $patient = Patient::factory()->create();
        $template = ReportTemplate::factory()->create(['is_active' => true]);

        $initResponse = $this->postJson('/reports', [
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ], $this->authHeader());
        $reportId = $initResponse->json('id');

        $response = $this->putJson("/reports/{$reportId}", [
            'values' => ['campo_1' => 'valor_1', 'campo_2' => 42],
        ], $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals('valor_1', $response->json('values.campo_1'));
        $this->assertEquals(42, $response->json('values.campo_2'));
    }

    public function test_save_draft_only_allows_author(): void
    {
        $author = $this->actingWithPermissions(['report.edit', 'report.create']);

        $patient = Patient::factory()->create();
        $template = ReportTemplate::factory()->create(['is_active' => true]);

        $initResponse = $this->postJson('/reports', [
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ], $this->authHeader());
        $reportId = $initResponse->json('id');

        // Different user tries to edit
        $otherUser = User::factory()->create();
        $this->mockJwtForUserId($otherUser->id);
        $this->grantPermission($otherUser, 'report.edit');

        $response = $this->putJson("/reports/{$reportId}", [
            'values' => ['campo' => 'intruso'],
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_save_draft_requires_draft_status(): void
    {
        $user = $this->actingWithPermissions(['report.edit', 'report.sign']);

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Signed,
            'signed_at' => Carbon::now(),
        ]);

        $response = $this->putJson("/reports/{$report->id}", [
            'values' => ['campo' => 'valor'],
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    // ─── SIGN ────────────────────────────────────────────────

    public function test_sign_updates_status_to_signed(): void
    {
        $user = $this->actingWithPermissions(['report.sign', 'report.create']);

        $patient = Patient::factory()->create();
        $template = ReportTemplate::factory()->create(['is_active' => true]);

        $initResponse = $this->postJson('/reports', [
            'patient_id' => $patient->id,
            'template_id' => $template->id,
        ], $this->authHeader());
        $reportId = $initResponse->json('id');

        $response = $this->postJson("/reports/{$reportId}/sign", [
            'signature' => 'data:image/png;base64,iVBORw0KGgo=',
        ], $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals('signed', $response->json('status'));
        $this->assertNotNull($response->json('signed_at'));
        $this->assertNotNull($response->json('signature_path'));
    }

    public function test_sign_requires_base64_signature(): void
    {
        $user = $this->actingWithPermission('report.sign');

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Draft,
        ]);

        $response = $this->postJson("/reports/{$report->id}/sign", [
            'signature' => '',
        ], $this->authHeader());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_sign_requires_draft_status(): void
    {
        $user = $this->actingWithPermission('report.sign');

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Archived,
            'signed_at' => Carbon::now(),
            'archived_at' => Carbon::now(),
        ]);

        $response = $this->postJson("/reports/{$report->id}/sign", [
            'signature' => 'iVBORw0KGgo=',
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_sign_only_author_can_sign(): void
    {
        $author = User::factory()->create();
        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $author->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Draft,
        ]);

        $otherUser = User::factory()->create();
        $this->mockJwtForUserId($otherUser->id);
        $this->grantPermission($otherUser, 'report.sign');

        $response = $this->postJson("/reports/{$report->id}/sign", [
            'signature' => 'iVBORw0KGgo=',
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    // ─── ARCHIVE ─────────────────────────────────────────────

    public function test_archive_updates_status_to_archived(): void
    {
        $user = $this->actingWithPermissions(['report.archive', 'report.sign']);

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Signed,
            'signed_at' => Carbon::now(),
        ]);

        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->postJson("/reports/{$report->id}/archive", [
            'pdf' => $fakePdf,
        ], $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals('archived', $response->json('status'));
        $this->assertNotNull($response->json('archived_at'));
        $this->assertNotNull($response->json('pdf_path'));
    }

    public function test_archive_requires_signed_status(): void
    {
        $user = $this->actingWithPermission('report.archive');

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Draft,
        ]);

        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->postJson("/reports/{$report->id}/archive", [
            'pdf' => $fakePdf,
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_archive_only_author_can_archive(): void
    {
        $author = User::factory()->create();
        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->signed()->create([
            'user_id' => $author->id,
            'template_id' => $template->id,
        ]);

        $otherUser = User::factory()->create();
        $this->mockJwtForUserId($otherUser->id);
        $this->grantPermission($otherUser, 'report.archive');

        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->postJson("/reports/{$report->id}/archive", [
            'pdf' => $fakePdf,
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    // ─── PDF DOWNLOAD ────────────────────────────────────────

    public function test_download_pdf_returns_file_for_archived_report(): void
    {
        $user = $this->actingWithPermission('report.download-pdf');

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->archived()->create([
            'template_id' => $template->id,
            'values' => ['diagnostico' => 'Test'],
        ]);

        // Ensure the PDF file actually exists on disk
        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
        $fakePdf->storeAs(dirname($report->pdf_path), basename($report->pdf_path), 'local');

        $response = $this->getJson("/reports/{$report->id}/pdf", $this->authHeader());

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_download_pdf_requires_signed_or_archived(): void
    {
        $user = $this->actingWithPermission('report.download-pdf');

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => ReportStatus::Draft,
        ]);

        $response = $this->getJson("/reports/{$report->id}/pdf", $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_download_pdf_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->archived()->create([
            'template_id' => $template->id,
        ]);

        $response = $this->getJson("/reports/{$report->id}/pdf", $this->authHeader());

        $response->assertStatus(403);
    }
}
