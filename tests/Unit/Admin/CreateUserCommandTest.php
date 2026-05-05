<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\CreateUserCommand;
use App\Repositories\User\SaveUserRepository;
use App\Exceptions\PermissionDeniedException;
use App\Models\User;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_throws_when_not_authenticated(): void
    {
        $this->expectException(PermissionDeniedException::class);

        $repo = $this->createMock(SaveUserRepository::class);
        $command = new CreateUserCommand($repo);

        $command->execute(['name' => 'Juan', 'email' => 'j@x.com']);
    }

    public function test_execute_calls_repository_and_returns_user_when_user_has_permission(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $expectedUser = User::factory()->make(['email' => 'new@example.com']);

        $repo = $this->createMock(SaveUserRepository::class);
        $repo->expects($this->once())->method('crear')->willReturn($expectedUser);

        $permissionService = $this->createMock(\App\Services\PermissionService::class);
        $permissionService->expects($this->once())->method('ensure')->with($actor, 'admin.user.create');
        $this->app->instance(\App\Services\PermissionService::class, $permissionService);

        $command = new CreateUserCommand($repo);
        $result = $command->execute(['name' => 'Nuevo', 'email' => 'new@example.com']);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('new@example.com', $result->email);
    }
}
