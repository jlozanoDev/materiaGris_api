<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof PermissionDeniedException) {
            return new JsonResponse([
                'message' => $e->getMessage() ?: 'Unauthorized',
            ], 401);
        }

        return parent::render($request, $e);
    }
}
