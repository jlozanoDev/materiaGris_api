<?php

namespace Tests\Unit\Reports;

use App\Http\Actions\Reports\ArchiveReportAction;
use App\Commands\Reports\ArchiveReportCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ArchiveReportActionTest extends TestCase
{
    public function test_invoke_returns_403_on_permission_denied(): void
    {
        $command = $this->createMock(ArchiveReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new PermissionDeniedException('Sin permisos'));

        $action = new ArchiveReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_invoke_returns_422_on_runtime_exception(): void
    {
        $command = $this->createMock(ArchiveReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('No se puede archivar'));

        $action = new ArchiveReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_invoke_returns_500_on_generic_exception(): void
    {
        Log::spy();

        $command = $this->createMock(ArchiveReportCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Error crítico'));

        $action = new ArchiveReportAction($command);
        $response = $action->__invoke(1);

        $this->assertEquals(500, $response->getStatusCode());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, 'ArchiveReportAction error: Error crítico'));
    }
}
