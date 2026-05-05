<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;

class RequirePermissions
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('require_permissions:patients.view|patients.edit,mode=all')
     */
    public function handle(Request $request, Closure $next, string $permissions = '', string $mode = 'any')
    {
        $permissionService = app(PermissionService::class);
        $auditService = app(AuditService::class);

        $perms = preg_split('/[|,]/', $permissions, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            $permissionService->ensure($user, $perms, $mode);
        } catch (PermissionDeniedException $e) {
            // record audit of denial
            try {
                $auditService->record('policy.denied', $user, null, ['route' => $request->path(), 'permissions' => $perms], ['module' => 'auth']);
            } catch (\Throwable $ex) {
                // don't break the flow if audit fails
            }

            // Return 401 response directly to ensure consistent HTTP response
            return response()->json([
                'message' => $e->getMessage() ?: 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
