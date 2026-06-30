<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;

class RefreshTokenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_refresh_token(): void
    {
        $user = User::factory()->create();

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'test-refresh-value'),
            'jti' => 'jti-test-123',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'id' => $token->id,
            'user_id' => $user->id,
            'jti' => 'jti-test-123',
        ]);
    }

    public function test_fillable_fields_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $expiresAt = Carbon::now()->addDays(30);

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'abc123hash',
            'jti' => 'jti-xyz',
            'ip' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'expires_at' => $expiresAt,
            'revoked' => false,
        ]);

        $this->assertEquals($user->id, $token->user_id);
        $this->assertEquals('abc123hash', $token->token_hash);
        $this->assertEquals('jti-xyz', $token->jti);
        $this->assertEquals('10.0.0.1', $token->ip);
        $this->assertEquals('Mozilla/5.0', $token->user_agent);
        $this->assertEquals($expiresAt->toDateTimeString(), $token->expires_at->toDateTimeString());
        $this->assertFalse($token->revoked);
    }

    public function test_revoked_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash123',
            'jti' => 'jti-revoked',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => true,
        ]);

        $this->assertTrue($token->revoked);
    }

    public function test_expires_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $expiresAt = Carbon::now()->addDays(30);

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash-datetime',
            'jti' => 'jti-dt',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => $expiresAt,
            'revoked' => false,
        ]);

        $this->assertInstanceOf(Carbon::class, $token->expires_at);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hash-relation',
            'jti' => 'jti-rel',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $this->assertInstanceOf(User::class, $token->user);
        $this->assertEquals($user->id, $token->user->id);
    }
}
