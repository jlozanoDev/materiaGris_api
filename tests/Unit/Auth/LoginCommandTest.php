<?php

namespace Tests\Unit\Auth;

use App\Commands\Auth\LoginCommand;
use App\DTOs\TokenPair;
use App\Repositories\User\GetUserRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginCommandTest extends TestCase
{
    public function test_execute_returns_tokens_on_valid_credentials(): void
    {
        $email = 'test@example.com';
        $password = 'password';
        $ip = '127.0.0.1';
        $userAgent = 'phpunit';

        $user = new \App\Models\User();
        $user->id = 1;
        $user->password = Hash::make($password);

        $leer = $this->createMock(GetUserRepository::class);
        $leer->method('buscarPorEmail')->with($email)->willReturn($user);

        $tokens = TokenPair::fromArray([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'jti' => 'jti-123',
            'access_expires_at' => now()->addHour()->timestamp,
            'refresh_expires_at' => now()->addDays(14)->timestamp,
        ]);

        $jwt = $this->createMock(JwtService::class);
        $jwt->method('issue')->with($user->id, [])->willReturn($tokens);

        $refreshEscribir = $this->createMock(SaveRefreshTokenRepository::class);
        $refreshEscribir->expects($this->once())->method('guardar')->with(
            $user,
            $tokens->refreshToken,
            $tokens->jti,
            $ip,
            $userAgent,
            $tokens->refreshExpiresAt
        );

        $command = new LoginCommand($leer, $refreshEscribir, $jwt);

        $result = $command->execute($email, $password, $ip, $userAgent);

        $this->assertSame($tokens, $result);
    }

    public function test_execute_throws_on_invalid_credentials(): void
    {
        $email = 'bad@example.com';
        $password = 'wrong';

        $leer = $this->createMock(GetUserRepository::class);
        $leer->method('buscarPorEmail')->with($email)->willReturn(null);

        $jwt = $this->createMock(JwtService::class);
        $refreshEscribir = $this->createMock(SaveRefreshTokenRepository::class);

        $command = new LoginCommand($leer, $refreshEscribir, $jwt);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nombre de usuario o contraseña inválidos');

        $command->execute($email, $password, '127.0.0.1', 'phpunit');
    }
}
