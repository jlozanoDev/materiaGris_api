<?php

namespace App\Repositories\RefreshToken;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;

class SaveRefreshTokenRepository
{
    public function guardar(User $user, string $refreshToken, string $jti, string $ip, string $userAgent, string $expiresAtIso): RefreshToken
    {
        return RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($refreshToken),
            'jti' => $jti,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => new DateTimeImmutable($expiresAtIso),
            'revoked' => false,
        ]);
    }

    public function revocar(RefreshToken $rt): void
    {
        $rt->revoked = true;
        $rt->save();
    }

    public function revocarTodosDeUsuario(int $userId): void
    {
        RefreshToken::where('user_id', $userId)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }
}
