<?php

namespace Tests\Unit\Admin\SystemVariable;

use App\Http\Actions\Admin\SystemVariable\GetSystemVariablesAction;
use App\Commands\Admin\SystemVariable\GetSystemVariablesCommand;
use Tests\TestCase;

class GetSystemVariablesActionTest extends TestCase
{
    public function test_execute_delegates_to_command(): void
    {
        $expected = [['category' => 'paciente', 'key' => 'nombre']];

        $command = $this->createMock(GetSystemVariablesCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willReturn($expected);

        $action = new GetSystemVariablesAction($command);
        $result = $action->execute();

        $this->assertSame($expected, $result);
    }

    public function test_invoke_returns_json_response(): void
    {
        $data = [['category' => 'paciente', 'key' => 'nombre']];

        $command = $this->createMock(GetSystemVariablesCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->willReturn($data);

        $action = new GetSystemVariablesAction($command);
        $response = $action->__invoke();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($data, $response->getData(true));
    }
}
