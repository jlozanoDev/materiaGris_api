<?php

namespace Tests\Unit\Auth;

use App\Commands\Auth\MeCommand;
use App\Repositories\User\GetUserRepository;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Role;
use Tests\TestCase;

class MeCommandTest extends TestCase
{
    public function test_execute_returns_null_when_user_not_found(): void
    {
        $leer = $this->createMock(GetUserRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorId')
            ->with(999)
            ->willReturn(null);

        $permService = $this->createMock(PermissionService::class);
        $permService->expects($this->never())->method('getEffectivePermissions');

        $command = new MeCommand($leer, $permService);
        $result = $command->execute(999);

        $this->assertNull($result);
    }

    public function test_execute_returns_user_data_when_found(): void
    {
        $user = new User();
        $user->id = 42;
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->setRelation('roles', collect([]));

        $leer = $this->createMock(GetUserRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorId')
            ->with(42)
            ->willReturn($user);

        $permService = $this->createMock(PermissionService::class);
        $permService->expects($this->once())
            ->method('getEffectivePermissions')
            ->with($user)
            ->willReturn([]);

        $command = new MeCommand($leer, $permService);
        $result = $command->execute(42);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('permissions_version', $result);
        $this->assertEquals(42, $result['id']);
        $this->assertEquals('Test User', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function test_execute_includes_roles_and_permissions(): void
    {
        $role1 = new Role();
        $role1->slug = 'medico';

        $role2 = new Role();
        $role2->slug = 'admin';

        $user = new User();
        $user->id = 1;
        $user->name = 'Doctor';
        $user->email = 'dr@example.com';
        $user->setRelation('roles', collect([$role1, $role2]));

        $leer = $this->createMock(GetUserRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorId')
            ->with(1)
            ->willReturn($user);

        $permissionsList = [
            ['slug' => 'patient.view', 'grant' => 1],
            ['slug' => 'report.create', 'grant' => 1],
        ];

        $permService = $this->createMock(PermissionService::class);
        $permService->expects($this->once())
            ->method('getEffectivePermissions')
            ->with($user)
            ->willReturn($permissionsList);

        $command = new MeCommand($leer, $permService);
        $result = $command->execute(1);

        $this->assertEquals(['medico', 'admin'], $result['roles']);
        $this->assertCount(2, $result['permissions']);
        $this->assertEquals($permissionsList, $result['permissions']);
    }
}
