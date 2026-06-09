<?php

namespace App\Http\Actions\Admin\TipoInforme;

use App\Commands\Admin\TipoInforme\ListTiposInformeCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListTiposInformeAction
{
    public function __construct(
        private ListTiposInformeCommand $command,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'q', 'per_page']);
            $paginator = $this->command->execute($filters);
            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('ListTiposInformeAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
