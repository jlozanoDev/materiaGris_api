<?php

namespace App\Commands\Auth;

use App\Repositories\RefreshToken\GetRefreshTokenRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use Illuminate\Support\Facades\Hash;

class LogoutCommand
{
    private GetRefreshTokenRepository $leer;
    private SaveRefreshTokenRepository $escribir;

    public function __construct(GetRefreshTokenRepository $leer, SaveRefreshTokenRepository $escribir)
    {
        $this->leer = $leer;
        $this->escribir = $escribir;
    }

    /**
     * @throws \RuntimeException if token missing or invalid
     */
    public function execute(?string $refreshValue): void
    {
        if (! $refreshValue) {
            throw new \RuntimeException('No refresh token');
        }

        $rt = $this->leer->buscarUltimoNoRevocado();
        if (! $rt || ! Hash::check($refreshValue, $rt->token_hash)) {
            throw new \RuntimeException('Invalid refresh token');
        }

        $this->escribir->revocar($rt);
    }
}
