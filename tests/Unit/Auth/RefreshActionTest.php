<?php

namespace Tests\Unit\Auth;

use App\Http\Actions\Auth\RefreshAction;
use App\Commands\Auth\RefreshCommand;
use Illuminate\Http\Request;
use Tests\TestCase;

class RefreshActionTest extends TestCase
{
    public function test_execute_returns_tokens_when_cookie_present(): void
    {
        $refreshValue = 'refresh-token-xyz';

        $tokens = [
            'access_token' => 'a',
            'refresh_token' => $refreshValue,
            'access_expires_at' => now()->addHour()->timestamp,
        ];

        $command = $this->createMock(RefreshCommand::class);
        $command->expects($this->once())->method('execute')->with($refreshValue, '127.0.0.1', 'phpunit')->willReturn($tokens);

        $action = new RefreshAction($command);

        $request = Request::create('/auth/refresh', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'phpunit');
        $request->cookies->set(config('jwt.cookie_name'), $refreshValue);

        $result = $action->execute($request);

        $this->assertSame($tokens, $result);
    }

    public function test_execute_throws_when_cookie_missing(): void
    {
        $command = $this->createMock(RefreshCommand::class);
        $action = new RefreshAction($command);

        $request = Request::create('/auth/refresh', 'POST');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No refresh token');

        $action->execute($request);
    }

    public function test_invoke_returns_access_token_and_expires_at(): void
    {
        $refreshValue = 'refresh-invoke-test';

        $tokens = [
            'access_token' => 'at',
            'refresh_token' => $refreshValue,
            'jti' => 'jti-x',
            'access_expires_at' => now()->addHour()->timestamp,
            'refresh_expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $command = $this->createMock(RefreshCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with($refreshValue, '127.0.0.1', 'phpunit')
            ->willReturn($tokens);

        $action = new RefreshAction($command);

        $request = Request::create('/auth/refresh', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'phpunit');
        $request->cookies->set(config('jwt.cookie_name'), $refreshValue);

        $result = $action->__invoke($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertEquals('at', $result['access_token']);
        $this->assertEquals($tokens['access_expires_at'], $result['expires_at']);
    }

    public function test_invoke_returns_401_when_runtime_exception(): void
    {
        $command = $this->createMock(RefreshCommand::class);
        $action = new RefreshAction($command);

        $request = Request::create('/auth/refresh', 'POST');

        $response = $action->__invoke($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
