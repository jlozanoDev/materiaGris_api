<?php

namespace App\Repositories\RefreshToken;

use App\Models\RefreshToken;

class GetRefreshTokenRepository
{
    public function buscarUltimoNoRevocado(): ?RefreshToken
    {
        return RefreshToken::where('revoked', false)->orderBy('id', 'desc')->first();
    }
}
