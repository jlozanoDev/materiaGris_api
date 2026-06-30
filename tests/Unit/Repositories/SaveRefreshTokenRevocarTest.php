<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;

class SaveRefreshTokenRevocarTest extends TestCase
{
    use RefreshDatabase;

    public function test_revocar_sets_revoked_to_true(): void
    {
        $user = User::factory()->create();

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash123',
            'jti' => 'jti-test',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $repo = new SaveRefreshTokenRepository();
        $repo->revocar($token);

        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'id' => $token->id,
            'revoked' => true,
        ]);
    }

    public function test_revocar_todos_de_usuario_revokes_all_active_tokens(): void
    {
        $user = User::factory()->create();

        $token1 = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash1',
            'jti' => 'jti-1',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $token2 = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash2',
            'jti' => 'jti-2',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $repo = new SaveRefreshTokenRepository();
        $repo->revocarTodosDeUsuario($user->id);

        $this->assertDatabaseHas('jwt_refresh_tokens', ['id' => $token1->id, 'revoked' => true]);
        $this->assertDatabaseHas('jwt_refresh_tokens', ['id' => $token2->id, 'revoked' => true]);
    }
}
