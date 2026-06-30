<?php

namespace Tests\Unit\Patients;

use App\Http\Actions\Patients\UpdatePatientAction;
use App\Commands\Admin\Patient\UpdatePatientCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdatePatientActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(UpdatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new UpdatePatientAction($command);

        $request = Request::create('/patients/1', 'PUT', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request, 1);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_422_on_runtime_exception(): void
    {
        $command = $this->createMock(UpdatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Paciente no encontrado'));

        $action = new UpdatePatientAction($command);

        $request = Request::create('/patients/1', 'PUT', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request, 1);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(UpdatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('DB error'));

        $action = new UpdatePatientAction($command);

        $request = Request::create('/patients/1', 'PUT', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request, 1);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, '[UpdatePatientAction] DB error'));
    }
}
