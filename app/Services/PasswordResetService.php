<?php

namespace App\Services;

use App\Mail\PasswordResetMail;
use App\Repositories\PasswordReset\PasswordResetRepository;
use App\Repositories\RefreshToken\SaveRefreshTokenRepository;
use App\Repositories\User\GetUserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(
        private PasswordResetRepository $resetRepository,
        private GetUserRepository $userLeer,
        private SaveRefreshTokenRepository $refreshEscribir,
    ) {}

    /**
     * Solicitar el reseteo de contraseña para un email.
     *
     * - Genera un token criptográficamente seguro.
     * - Lo persiste (hasheado) en password_reset_tokens.
     * - Envía el email con el enlace de forma síncrona.
     *
     * Si el email no existe NO se lanza error (evitar enumeración de usuarios).
     */
    public function solicitarReseteo(string $email): void
    {
        $user = $this->userLeer->buscarPorEmail($email);

        if (! $user) {
            // Respuesta silenciosa para no filtrar existencia del email.
            return;
        }

        $token = Str::random(64);

        $this->resetRepository->crear($email, $token);

        $enlace = $this->buildLink($email, $token);

        Mail::to($user->email)->send(new PasswordResetMail($user, $enlace));
    }

    /**
     * Aplicar el reseteo de contraseña.
     *
     * - Valida que el token sea correcto y no haya expirado.
     * - Actualiza la contraseña del usuario (hash bcrypt).
     * - Elimina el token consumido.
     *
     * @throws \RuntimeException si el token no es válido o ha expirado.
     */
    public function aplicarReseteo(string $email, string $plainToken, string $nuevaPassword): void
    {
        if (! $this->resetRepository->esValido($email, $plainToken)) {
            throw new \RuntimeException('El token de reseteo es inválido o ha expirado.');
        }

        $user = $this->userLeer->buscarPorEmail($email);

        if (! $user) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $user->password = Hash::make($nuevaPassword);
        $user->save();

        $this->resetRepository->eliminar($email);

        $this->refreshEscribir->revocarTodosDeUsuario($user->id);
    }

    private function buildLink(string $email, string $token): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        return $frontendUrl . '/reset-password?' . http_build_query([
            'token' => $token,
            'email' => $email,
        ]);
    }
}
