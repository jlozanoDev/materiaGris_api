<?php

namespace App\Repositories\RefreshToken;

use App\Models\RefreshToken;

class GetRefreshTokenRepository
{
    public function buscarPorHash(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
