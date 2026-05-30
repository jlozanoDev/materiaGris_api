<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\LoginCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LoginAction
{
    private LoginCommand $command;

    public function __construct(LoginCommand $command)
    {
        $this->command = $command;
    }

    public function execute(Request $request): array
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        return $this->command->execute(
            $request->input('email'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent()
        );
    }

    public function __invoke(Request $request)
    {
        try {
            $tokens = $this->execute($request);

            $secure = config('app.env') === 'production';
            $sameSite = $secure ? 'None' : 'Lax';
            Cookie::queue(Cookie::make(
                config('jwt.cookie_name'),
                $tokens['refresh_token'],
                config('jwt.refresh_ttl') * 24 * 60,
                null,
                config('jwt.cookie_domain'),
                true,
                true,
                false,
                $sameSite
            ));

            return [
                'access_token' => $tokens['access_token'],
                'expires_at' => $tokens['access_expires_at'],
            ];
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Nombre de usuario o contraseña inválidos'], 401);
        }
    }
}
