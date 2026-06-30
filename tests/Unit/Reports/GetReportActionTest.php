<?php

namespace Tests\Unit\Reports;

use App\Http\Actions\Reports\GetReportAction;
use App\Commands\Reports\GetReportCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GetReportActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(GetReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new GetReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_404_on_runtime_exception(): void
    {
        $command = $this->createMock(GetReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Informe no encontrado'));

        $action = new GetReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(GetReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('DB caída'));

        $action = new GetReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, 'GetReportAction error: DB caída'));
    }
}
