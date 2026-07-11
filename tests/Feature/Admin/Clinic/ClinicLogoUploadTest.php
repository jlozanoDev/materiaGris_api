<?php

namespace Tests\Feature\Admin\Clinic;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Clinic;
use App\Services\JwtService;

class ClinicLogoUploadTest extends TestCase
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

    private function createClinicAndUser(): array
    {
        $clinic = Clinic::factory()->create();
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);
        $this->grantPermission($user, 'admin.clinic.update');
        return [$clinic, $user];
    }

    // ─── POST /admin/clinic/logo ─────────────────────────────

    public function test_upload_logo_returns_logo(): void
    {
        Storage::fake('public');
        [$clinic, $user] = $this->createClinicAndUser();

        $response = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ], $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonStructure(['logo']);
        $this->assertNotNull(Clinic::first()->logo);

        Storage::disk('public')->assertExists('logos/' . Clinic::first()->logo);
    }

    public function test_upload_logo_requires_auth(): void
    {
        $response = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_logo_requires_permission(): void
    {
        Clinic::factory()->create();
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ], $this->authHeader());

        $response->assertStatus(403);
    }

    public function test_upload_rejects_invalid_mime(): void
    {
        [$clinic, $user] = $this->createClinicAndUser();

        $response = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        [$clinic, $user] = $this->createClinicAndUser();

        $response = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('huge.png')->size(6000),
        ], $this->authHeader());

        $response->assertStatus(422);
    }

    public function test_upload_replaces_old_logo(): void
    {
        Storage::fake('public');
        [$clinic, $user] = $this->createClinicAndUser();

        $response1 = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo1.png', 200, 200),
        ], $this->authHeader());
        $response1->assertStatus(200);

        $oldFilename = Clinic::first()->logo;
        $oldPath = 'logos/' . $oldFilename;

        $response2 = $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo2.png', 200, 200),
        ], $this->authHeader());
        $response2->assertStatus(200);

        $newFilename = Clinic::first()->logo;

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists('logos/' . $newFilename);
        $this->assertNotEquals($oldFilename, $newFilename);
    }

    // ─── GET /logos/{filename} ───────────────────────────────

    public function test_get_logos_serves_file(): void
    {
        Storage::fake('public');
        [$clinic, $user] = $this->createClinicAndUser();

        $this->postJson('/admin/clinic/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ], $this->authHeader());

        $filename = Clinic::first()->logo;

        $response = $this->get('/logos/' . $filename);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Access-Control-Allow-Origin'); // Set by global CORS middleware
    }

    public function test_get_logos_404_on_missing(): void
    {
        $response = $this->get('/logos/nonexistent.png');
        $response->assertStatus(404);
    }

    // ─── GET /admin/clinic includes logo ─────────────────

    public function test_get_admin_clinic_includes_logo(): void
    {
        Clinic::factory()->create();
        $user = User::factory()->create();
        $this->mockJwtForUserId($user->id);

        $response = $this->getJson('/admin/clinic', $this->authHeader());

        $response->assertStatus(200);
        $response->assertJsonStructure(['logo']);
        $this->assertNull($response->json('logo'));
    }
}
