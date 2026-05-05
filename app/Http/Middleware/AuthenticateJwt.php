<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use App\Models\User;

class AuthenticateJwt
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization');
        if (! $auth || ! str_starts_with($auth, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tokenStr = substr($auth, 7);
        try {
            $token = $this->jwtService->parseAndValidate($tokenStr);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[AuthenticateJwt] parse/validate exception: ' . $e->getMessage(), [
                'token_preview' => substr($tokenStr, 0, 30) . '...',
            ]);
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (! $token) {
            \Illuminate\Support\Facades\Log::error('[AuthenticateJwt] token validation failed (returned null)');
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $sub = $token->claims()->get('sub');
        $user = User::find($sub);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
