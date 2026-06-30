<?php

namespace Tests\Unit\Auth;

use App\Http\Actions\Auth\LoginAction;
use App\Commands\Auth\LoginCommand;
use App\DTOs\TokenPair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class LoginActionTest extends TestCase
{
    public function test_invoke_returns_tokens_and_queues_cookie_on_success(): void
    {
        $tokens = TokenPair::fromArray([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'access_expires_at' => now()->addHour()->timestamp,
            'refresh_expires_at' => now()->addDays(14)->timestamp,
            'jti' => 'jti-1',
        ]);

        $command = $this->createMock(LoginCommand::class);
        $command->expects($this->once())->method('execute')->willReturn($tokens);

        $action = new LoginAction($command);

        $request = Request::create('/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'phpunit');


        $result = $action->__invoke($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_at', $result);
    }

    public function test_invoke_returns_401_on_invalid_credentials(): void
    {
        $command = $this->createMock(LoginCommand::class);
        $command->expects($this->once())->method('execute')->will($this->throwException(new \RuntimeException('Nombre de usuario o contraseña inválidos')));

        $action = new LoginAction($command);

        $request = Request::create('/auth/login', 'POST', [
            'email' => 'bad@example.com',
            'password' => 'wrong',
        ]);

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame(['message' => 'Nombre de usuario o contraseña inválidos'], $response->getData(true));
    }
}
