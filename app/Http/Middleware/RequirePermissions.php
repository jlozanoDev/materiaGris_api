<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\Request;

class RequirePermissions
{
    private PermissionService $permissionService;
    private AuditService $auditService;

    public function __construct(PermissionService $permissionService, AuditService $auditService)
    {
        $this->permissionService = $permissionService;
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     * Usage: ->middleware('require_permissions:patients.view|patients.edit,mode=all')
     */
    public function handle(Request $request, Closure $next, string $permissions = '', string $mode = 'any')
    {
        $perms = preg_split('/[|,]/', $permissions, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (str_starts_with($mode, 'mode=')) {
            $mode = substr($mode, 5);
        }

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            $this->permissionService->ensure($user, $perms, $mode);
        } catch (PermissionDeniedException $e) {
            // record audit of denial
            try {
                $this->auditService->record('policy.denied', $user, null, ['route' => $request->path(), 'permissions' => $perms], ['module' => 'auth']);
            } catch (\Throwable $ex) {
                // don't break the flow if audit fails
            }

            // Return 403 response directly to ensure consistent HTTP response
            return response()->json([
                'message' => $e->getMessage() ?: 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
