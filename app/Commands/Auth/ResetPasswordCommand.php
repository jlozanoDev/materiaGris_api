<?php

namespace App\Commands\Auth;

use App\Services\PasswordResetService;

class ResetPasswordCommand
{
    public function __construct(
        private PasswordResetService $service,
    ) {}

    /**
     * Orquesta la aplicación del reseteo de contraseña.
     * Delega toda la lógica al PasswordResetService.
     *
     * @throws \RuntimeException si el token es inválido o el usuario no existe.
     */
    public function execute(string $email, string $token, string $nuevaPassword): void
    {
        $this->service->aplicarReseteo($email, $token, $nuevaPassword);
    }
}
