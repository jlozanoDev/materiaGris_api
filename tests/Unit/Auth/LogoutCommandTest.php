<?php

namespace Tests\Unit\Auth;

use App\Commands\Auth\LogoutCommand;
use App\Repositories\RefreshToken\GetRefreshTokenRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Models\RefreshToken;
use Tests\TestCase;

class LogoutCommandTest extends TestCase
{
    public function test_execute_revokes_token_when_valid(): void
    {
        $refreshValue = 'valid-refresh-token-value';
        $tokenHash = hash('sha256', $refreshValue);

        $token = new RefreshToken();
        $token->id = 1;

        $leer = $this->createMock(GetRefreshTokenRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorHash')
            ->with($tokenHash)
            ->willReturn($token);

        $escribir = $this->createMock(SaveRefreshTokenRepository::class);
        $escribir->expects($this->once())
            ->method('revocar')
            ->with($token);

        $command = new LogoutCommand($leer, $escribir);
        $command->execute($refreshValue);

        $this->assertTrue(true);
    }

    public function test_execute_throws_when_refresh_value_is_null(): void
    {
        $leer = $this->createMock(GetRefreshTokenRepository::class);
        $leer->expects($this->never())->method('buscarPorHash');

        $escribir = $this->createMock(SaveRefreshTokenRepository::class);
        $escribir->expects($this->never())->method('revocar');

        $command = new LogoutCommand($leer, $escribir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No refresh token');

        $command->execute(null);
    }

    public function test_execute_throws_when_token_not_found(): void
    {
        $refreshValue = 'nonexistent-token';
        $tokenHash = hash('sha256', $refreshValue);

        $leer = $this->createMock(GetRefreshTokenRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorHash')
            ->with($tokenHash)
            ->willReturn(null);

        $escribir = $this->createMock(SaveRefreshTokenRepository::class);
        $escribir->expects($this->never())->method('revocar');

        $command = new LogoutCommand($leer, $escribir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $command->execute($refreshValue);
    }
}
