<?php

namespace Tests\Unit\Auth;

use App\Http\Actions\Auth\MeAction;
use App\Commands\Auth\MeCommand;
use Illuminate\Http\Request;
use Tests\TestCase;

class MeActionTest extends TestCase
{
    public function test_execute_returns_command_result_when_user_present(): void
    {
        $user = new \App\Models\User();
        $user->id = 42;

        $expected = [
            'id' => 42,
            'name' => 'Tester',
            'email' => 'u@example.com',
            'roles' => [],
            'permissions' => [],
            'permissions_version' => now()->toIso8601String(),
        ];

        $command = $this->createMock(MeCommand::class);
        $command->expects($this->once())->method('execute')->with($user->id)->willReturn($expected);

        $action = new MeAction($command);

        $request = Request::create('/auth/me', 'GET');
        $request->setUserResolver(fn() => $user);

        $result = $action->execute($request);

        $this->assertSame($expected, $result);
    }

    public function test_execute_returns_null_when_no_user(): void
    {
        $command = $this->createMock(MeCommand::class);
        $command->expects($this->never())->method('execute');

        $action = new MeAction($command);

        $request = Request::create('/auth/me', 'GET');

        $this->assertNull($action->execute($request));
    }

    public function test_invoke_returns_json_when_user_present(): void
    {
        $user = new \App\Models\User();
        $user->id = 42;

        $expected = [
            'id' => 42,
            'name' => 'Tester',
            'email' => 'u@example.com',
            'roles' => [],
            'permissions' => [],
            'permissions_version' => now()->toIso8601String(),
        ];

        $command = $this->createMock(MeCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with($user->id)
            ->willReturn($expected);

        $action = new MeAction($command);

        $request = Request::create('/auth/me', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getData(true));
    }

    public function test_invoke_returns_401_when_no_user(): void
    {
        $command = $this->createMock(MeCommand::class);
        $command->expects($this->never())->method('execute');

        $action = new MeAction($command);

        $request = Request::create('/auth/me', 'GET');

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
