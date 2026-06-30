<?php

namespace Tests\Unit\Reports;

use App\Http\Actions\Reports\InitReportAction;
use App\Commands\Reports\InitReportCommand;
use App\Exceptions\PermissionDeniedException;
use App\Http\Requests\Reports\InitReportRequest;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InitReportActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(InitReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new InitReportAction($command);

        $request = $this->getMockBuilder(InitReportRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validated'])
            ->getMock();
        $request->expects($this->once())
            ->method('validated')
            ->willReturn(['template_id' => 1, 'patient_id' => 2]);

        $response = $action->__invoke($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_422_on_runtime_exception(): void
    {
        $command = $this->createMock(InitReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Plantilla inválida'));

        $action = new InitReportAction($command);

        $request = $this->getMockBuilder(InitReportRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validated'])
            ->getMock();
        $request->expects($this->once())
            ->method('validated')
            ->willReturn(['template_id' => 1, 'patient_id' => 2]);

        $response = $action->__invoke($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(InitReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Error fatal'));

        $action = new InitReportAction($command);

        $request = $this->getMockBuilder(InitReportRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validated'])
            ->getMock();
        $request->expects($this->once())
            ->method('validated')
            ->willReturn(['template_id' => 1, 'patient_id' => 2]);

        $response = $action->__invoke($request);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, 'InitReportAction error: Error fatal'));
    }
}
