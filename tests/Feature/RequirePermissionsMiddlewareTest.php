<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Permission;

class RequirePermissionsMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(\App\Http\Middleware\RequirePermissions::class . ':admin.user.view,any')
            ->get('/_test/require-perm', function () {
                return response()->json(['ok' => true]);
            });
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/_test/require-perm');

        $response->assertStatus(401);
    }

    public function test_denied_returns_403_and_audit_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/_test/require-perm');

        $response->assertStatus(403);
        $this->assertDatabaseHas('audits', ['type' => 'policy.denied']);
    }

    public function test_allowed_when_user_has_permission(): void
    {
        $user = User::factory()->create();
        $perm = Permission::firstOrCreate(['slug' => 'admin.user.view']);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);

        $response = $this->actingAs($user)->getJson('/_test/require-perm');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }
}
