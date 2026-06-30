<?php

namespace Tests\Unit\Admin\Patient;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Commands\Admin\Patient\GetPatientsCommand;
use App\Repositories\Patient\PatientReadRepository;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Permission;
use App\Exceptions\PermissionDeniedException;

class GetPatientsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithPermission(string $permission = 'patient.view'): User
    {
        $user = User::factory()->create();
        $perm = Permission::firstOrCreate(['slug' => $permission], ['name' => $permission]);
        $user->userPermissions()->syncWithoutDetaching([$perm->id => ['grant' => 1, 'origin' => 'user']]);
        return $user;
    }

    public function test_execute_throws_permission_denied_when_not_authenticated(): void
    {
        $leer = $this->createMock(PatientReadRepository::class);
        $permService = $this->app->make(PermissionService::class);

        $command = new GetPatientsCommand($leer, $permService);

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('Unauthorized');

        $command->execute();
    }

    public function test_execute_delegates_to_repository_with_filters(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $filters = ['search' => 'Juan', 'city' => 'Madrid'];
        $expectedResult = [['id' => 1, 'name' => 'Juan Pérez']];

        $leer = $this->createMock(PatientReadRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorFiltros')
            ->with($filters)
            ->willReturn($expectedResult);

        $permService = $this->app->make(PermissionService::class);

        $command = new GetPatientsCommand($leer, $permService);
        $result = $command->execute($filters);

        $this->assertSame($expectedResult, $result);
    }

    public function test_execute_works_with_empty_filters(): void
    {
        $user = $this->createUserWithPermission();
        $this->actingAs($user);

        $leer = $this->createMock(PatientReadRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorFiltros')
            ->with([])
            ->willReturn([]);

        $permService = $this->app->make(PermissionService::class);

        $command = new GetPatientsCommand($leer, $permService);
        $result = $command->execute();

        $this->assertSame([], $result);
    }
}
