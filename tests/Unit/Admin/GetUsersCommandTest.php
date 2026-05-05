<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\GetUsersCommand;
use App\Repositories\User\GetUserRepository;
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
        $command = new GetUsersCommand($repo);

        $command->execute();
    }

    public function test_execute_calls_repository_and_returns_collection_when_user_has_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $repo = $this->createMock(GetUserRepository::class);
        $repo->method('buscarTodos')->willReturn(collect([
            ['id' => 1, 'email' => 'a@example.com'],
        ]));

        $permissionService = $this->createMock(\App\Services\PermissionService::class);
        $permissionService->expects($this->once())->method('ensure')->with($user, 'admin.user.view');
        $this->app->instance(\App\Services\PermissionService::class, $permissionService);

        $command = new GetUsersCommand($repo);
        $result = $command->execute();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame([['id' => 1, 'email' => 'a@example.com']], $result->toArray());
    }
}
