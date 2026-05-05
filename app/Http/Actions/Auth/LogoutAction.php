<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\LogoutCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LogoutAction
{
    private LogoutCommand $command;

    public function __construct(LogoutCommand $command)
    {
        $this->command = $command;
    }

    public function execute(Request $request)
    {
        $cookieName = config('jwt.cookie_name');
        $refresh = $request->cookie($cookieName);

        $this->command->execute($refresh);
    }

    public function __invoke(Request $request)
    {
        try {
            $this->execute($request);
            Cookie::queue(Cookie::forget(config('jwt.cookie_name')));
            return ['message' => 'Logged out'];
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }
}
