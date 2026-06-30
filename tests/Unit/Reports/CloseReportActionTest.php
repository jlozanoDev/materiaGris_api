<?php

namespace Tests\Unit\Reports;

use App\Http\Actions\Reports\CloseReportAction;
use App\Commands\Reports\CloseReportCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloseReportActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(CloseReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new CloseReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_422_on_runtime_exception(): void
    {
        $command = $this->createMock(CloseReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('No se puede cerrar'));

        $action = new CloseReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(CloseReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Error crítico'));

        $action = new CloseReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, 'CloseReportAction error: Error crítico'));
    }
}
