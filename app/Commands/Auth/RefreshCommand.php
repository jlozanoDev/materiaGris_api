<?php

namespace App\Commands\Auth;

use App\Repositories\RefreshToken\GetRefreshTokenRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;

class RefreshCommand
{
    private GetRefreshTokenRepository $leer;
    private SaveRefreshTokenRepository $escribir;
    private JwtService $jwtService;

    public function __construct(GetRefreshTokenRepository $leer, SaveRefreshTokenRepository $escribir, JwtService $jwtService)
    {
        $this->leer = $leer;
        $this->escribir = $escribir;
        $this->jwtService = $jwtService;
    }

    /**
     * @throws \RuntimeException on invalid or missing refresh token
     */
    public function execute(string $refreshValue, string $ip, string $userAgent): array
    {
        $rt = $this->leer->buscarUltimoNoRevocado();
        if (! $rt || ! Hash::check($refreshValue, $rt->token_hash)) {
            throw new \RuntimeException('Invalid refresh token');
        }

        $user = $rt->user;
        $tokens = $this->jwtService->issue($user->id, []);

        $rt->revoked = true;
        $rt->save();

        $this->escribir->guardar($user, $tokens['refresh_token'], $tokens['jti'], $ip, $userAgent, $tokens['refresh_expires_at']);

        return $tokens;
    }
}
