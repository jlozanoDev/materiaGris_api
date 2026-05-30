<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\RefreshCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class RefreshAction
{
    private RefreshCommand $command;

    public function __construct(RefreshCommand $command)
    {
        $this->command = $command;
    }

    public function execute(Request $request)
    {
        $cookieName = config('jwt.cookie_name');
        $refresh = $request->cookie($cookieName);
        if (! $refresh) {
            throw new \RuntimeException('No refresh token');
        }

        return $this->command->execute($refresh, $request->ip(), $request->userAgent());
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
                true,   // httpOnly
                $secure,
                false,
                $sameSite
            ));

            return [
                'access_token' => $tokens['access_token'],
                'expires_at' => $tokens['access_expires_at'],
            ];
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }
}
