<?php

namespace Tests\Unit\Patients;

use App\Http\Actions\Patients\GetPatientsAction;
use App\Commands\Admin\Patient\GetPatientsCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GetPatientsActionTest extends TestCase
{
    public function test_execute_delegates_to_command(): void
    {
        $expected = [['id' => 1, 'first_name' => 'Juan']];

        $command = $this->createMock(GetPatientsCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(['search' => 'Juan'])
            ->willReturn($expected);

        $action = new GetPatientsAction($command);

        $request = Request::create('/patients/find', 'GET', ['search' => 'Juan']);

        $result = $action->execute($request);

        $this->assertSame($expected, $result);
    }

    public function test_invoke_returns_json_success(): void
    {
        $data = [['id' => 1, 'first_name' => 'Juan']];

        $command = $this->createMock(GetPatientsCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn($data);

        $action = new GetPatientsAction($command);

        $request = Request::create('/patients/find', 'GET');

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($data, $response->getData(true));
    }

    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(GetPatientsCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('No tenés permiso'));

        $action = new GetPatientsAction($command);

        $request = Request::create('/patients/find', 'GET');

        $response = $action->__invoke($request);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('No tenés permiso', $response->getData(true)['message']);
    }

    public function test_invoke_returns_400_on_runtime_exception(): void
    {
        $command = $this->createMock(GetPatientsCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Filtro inválido'));

        $action = new GetPatientsAction($command);

        $request = Request::create('/patients/find', 'GET');

        $response = $action->__invoke($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Filtro inválido', $response->getData(true)['message']);
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(GetPatientsCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('DB explosion'));

        $action = new GetPatientsAction($command);

        $request = Request::create('/patients/find', 'GET');

        $response = $action->__invoke($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal server error', $response->getData(true)['message']);

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, '[GetPatientsAction] DB explosion'));
    }
}
