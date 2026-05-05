<?php

namespace App\Commands\Auth;

use App\Services\PasswordResetService;

class ForgotPasswordCommand
{
    public function __construct(
        private PasswordResetService $service,
    ) {}

    /**
     * Orquesta la solicitud de reseteo de contraseña.
     * Delega toda la lógica al PasswordResetService.
     */
    public function execute(string $email): void
    {
        $this->service->solicitarReseteo($email);
    }
}
