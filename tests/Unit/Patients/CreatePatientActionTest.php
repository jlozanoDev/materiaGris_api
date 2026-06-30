<?php

namespace Tests\Unit\Patients;

use App\Http\Actions\Patients\CreatePatientAction;
use App\Commands\Admin\Patient\CreatePatientCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CreatePatientActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(CreatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new CreatePatientAction($command);

        $request = Request::create('/patients', 'POST', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_422_on_runtime_exception(): void
    {
        $command = $this->createMock(CreatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Dato inválido'));

        $action = new CreatePatientAction($command);

        $request = Request::create('/patients', 'POST', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(CreatePatientCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('DB error'));

        $action = new CreatePatientAction($command);

        $request = Request::create('/patients', 'POST', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $response = $action->__invoke($request);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, '[CreatePatientAction] DB error'));
    }
}
