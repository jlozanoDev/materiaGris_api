<?php

namespace App\Commands\Auth;

use App\Repositories\User\GetUserRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;

class LoginCommand
{
    private GetUserRepository $leer;
    private SaveRefreshTokenRepository $refreshEscribir;
    private JwtService $jwtService;

    public function __construct(GetUserRepository $leer, SaveRefreshTokenRepository $refreshEscribir, JwtService $jwtService)
    {
        $this->leer = $leer;
        $this->refreshEscribir = $refreshEscribir;
        $this->jwtService = $jwtService;
    }

    /**
     * @throws \RuntimeException on nombre de usuario o contraseña inválidos
     */
    public function execute(string $email, string $password, string $ip, string $userAgent): array
    {
        $user = $this->leer->buscarPorEmail($email);
        if (! $user || ! Hash::check($password, $user->password)) {
            throw new \RuntimeException('Nombre de usuario o contraseña inválidos');
        }

        $tokens = $this->jwtService->issue($user->id, []);

        $this->refreshEscribir->guardar($user, $tokens['refresh_token'], $tokens['jti'], $ip, $userAgent, $tokens['refresh_expires_at']);

        return $tokens;
    }
}
