<?php
namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticateJWT
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthenticationException | UnauthorizedHttpException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Token expired or invalid'
            ], 401);
        }
    }
}
