<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\ResetPasswordCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResetPasswordAction
{
    public function __construct(
        private ResetPasswordCommand $command,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        try {
            $this->command->execute(
                $request->input('email'),
                $request->input('token'),
                $request->input('password'),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.',
        ]);
    }
}
