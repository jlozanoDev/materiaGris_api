<?php

namespace Tests\Unit\Auth;

use App\Http\Actions\Auth\LogoutAction;
use App\Commands\Auth\LogoutCommand;
use Illuminate\Http\Request;
use Tests\TestCase;

class LogoutActionTest extends TestCase
{
    public function test_execute_calls_command_with_cookie_value(): void
    {
        $refreshValue = 'logout_refresh_123';

        $command = $this->createMock(LogoutCommand::class);
        $command->expects($this->once())->method('execute')->with($refreshValue);

        $action = new LogoutAction($command);

        $request = Request::create('/auth/logout', 'POST');
        $request->cookies->set(config('jwt.cookie_name'), $refreshValue);

        $action->execute($request);

        $this->assertTrue(true); // reached
    }

    public function test_invoke_returns_401_when_command_throws(): void
    {
        $command = $this->createMock(LogoutCommand::class);
        $command->expects($this->once())->method('execute')->will($this->throwException(new \RuntimeException('Invalid refresh')));

        $action = new LogoutAction($command);

        $request = Request::create('/auth/logout', 'POST');

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
