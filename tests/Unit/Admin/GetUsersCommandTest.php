<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\GetUsersCommand;
use App\Repositories\User\GetUserRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\User;
use Illuminate\Support\Collection;

class GetUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_throws_when_not_authenticated(): void
    {
        $this->expectException(PermissionDeniedException::class);

        $repo = $this->createMock(GetUserRepository::class);
        $permissionService = $this->createMock(PermissionService::class);
        $command = new GetUsersCommand($repo, $permissionService);

        $command->execute();
    }

    public function test_execute_calls_repository_and_returns_collection_when_user_has_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mockUser = User::factory()->make(['id' => 1, 'email' => 'a@example.com']);

        $repo = $this->createMock(GetUserRepository::class);
        $repo->method('buscarTodos')->willReturn(collect([$mockUser]));

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())->method('ensure')->with($user, 'admin.user.view');

        $command = new GetUsersCommand($repo, $permissionService);
        $result = $command->execute();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('a@example.com', $result->first()['email']);
    }
}
