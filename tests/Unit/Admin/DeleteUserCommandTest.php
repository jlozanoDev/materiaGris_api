<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\User\DeleteUserCommand;
use App\Repositories\User\GetUserRepository;
use App\Repositories\User\SaveUserRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\User;

class DeleteUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_throws_when_not_authenticated(): void
    {
        $this->expectException(PermissionDeniedException::class);

        $leer = $this->createMock(GetUserRepository::class);
        $escribir = $this->createMock(SaveUserRepository::class);
        $permissionService = $this->createMock(PermissionService::class);
        $command = new DeleteUserCommand($leer, $escribir, $permissionService);

        $command->execute(1);
    }

    public function test_execute_deletes_user_when_allowed(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $target = User::factory()->create();

        $leer = $this->createMock(GetUserRepository::class);
        $leer->method('buscarPorId')->willReturn($target);

        $escribir = $this->createMock(SaveUserRepository::class);
        $escribir->expects($this->once())->method('eliminar')->with($target);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())->method('ensure')->with($actor, 'admin.user.delete');

        $command = new DeleteUserCommand($leer, $escribir, $permissionService);
        $res = $command->execute($target->id);

        $this->assertTrue($res);
    }
}
