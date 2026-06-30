<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Permission;
use App\Exceptions\PermissionDeniedException;

class PermissionServiceAllModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_all_mode_throws_when_any_permission_missing(): void
    {
        $user = User::factory()->create();

        $perm1 = Permission::create([
            'name' => 'Admin User View',
            'slug' => 'allmode.user.view',
            'action' => 'view',
        ]);

        $perm2 = Permission::create([
            'name' => 'Admin Role View',
            'slug' => 'allmode.role.view',
            'action' => 'view',
        ]);

        $user->userPermissions()->syncWithoutDetaching([$perm1->id => ['grant' => 1, 'origin' => 'user']]);

        $service = $this->app->make(PermissionService::class);

        $this->expectException(PermissionDeniedException::class);

        $service->ensure($user, ['allmode.user.view', 'allmode.role.view'], 'all');
    }

    public function test_ensure_all_mode_passes_when_all_permissions_granted(): void
    {
        $user = User::factory()->create();

        $perm1 = Permission::create([
            'name' => 'Admin User View B',
            'slug' => 'allmode.userb.view',
            'action' => 'view',
        ]);

        $perm2 = Permission::create([
            'name' => 'Admin Role View B',
            'slug' => 'allmode.roleb.view',
            'action' => 'view',
        ]);

        $user->userPermissions()->syncWithoutDetaching([
            $perm1->id => ['grant' => 1, 'origin' => 'user'],
            $perm2->id => ['grant' => 1, 'origin' => 'user'],
        ]);

        $service = $this->app->make(PermissionService::class);

        $service->ensure($user, ['allmode.userb.view', 'allmode.roleb.view'], 'all');

        $this->assertTrue(true);
    }
}
