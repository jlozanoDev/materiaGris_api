<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\ForgotPasswordCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForgotPasswordAction
{
    public function __construct(
        private ForgotPasswordCommand $command,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // El comando no lanza error si el email no existe (evitar enumeración).
        $this->command->execute($request->input('email'));

        return response()->json([
            'message' => 'Si el email está registrado, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }
}
