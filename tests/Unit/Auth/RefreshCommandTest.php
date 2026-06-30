<?php

namespace Tests\Unit\Auth;

use App\Commands\Auth\RefreshCommand;
use App\DTOs\TokenPair;
use App\Repositories\RefreshToken\GetRefreshTokenRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Services\JwtService;
use App\Models\RefreshToken;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_issues_new_tokens_and_revokes_old_one(): void
    {
        $refreshValue = 'valid-refresh';
        $tokenHash = hash('sha256', $refreshValue);
        $ip = '192.168.1.1';
        $userAgent = 'TestAgent/1.0';

        $user = User::factory()->create();

        $oldToken = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'jti' => 'jti-old',
            'ip' => '127.0.0.1',
            'user_agent' => 'old-agent',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $newTokens = TokenPair::fromArray([
            'access_token' => 'new-access-token-hash',
            'refresh_token' => 'new-refresh-token-hash',
            'jti' => 'jti-abc-123',
            'access_expires_at' => now()->addHour()->timestamp,
            'refresh_expires_at' => now()->addDays(30)->toDateTimeString(),
        ]);

        $jwtService = $this->createMock(JwtService::class);
        $jwtService->expects($this->once())
            ->method('issue')
            ->with($user->id, [])
            ->willReturn($newTokens);

        $leer = new GetRefreshTokenRepository();
        $escribir = new SaveRefreshTokenRepository();

        $command = new RefreshCommand($leer, $escribir, $jwtService);
        $result = $command->execute($refreshValue, $ip, $userAgent);

        $this->assertSame($newTokens, $result);

        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'id' => $oldToken->id,
            'revoked' => true,
        ]);

        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'jti' => 'jti-abc-123',
            'revoked' => false,
        ]);
    }

    public function test_execute_throws_when_token_not_found(): void
    {
        $refreshValue = 'invalid-refresh';
        $tokenHash = hash('sha256', $refreshValue);

        $leer = $this->createMock(GetRefreshTokenRepository::class);
        $leer->expects($this->once())
            ->method('buscarPorHash')
            ->with($tokenHash)
            ->willReturn(null);

        $escribir = $this->createMock(SaveRefreshTokenRepository::class);
        $jwtService = $this->createMock(JwtService::class);

        $command = new RefreshCommand($leer, $escribir, $jwtService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $command->execute($refreshValue, '127.0.0.1', 'phpunit');
    }
}
