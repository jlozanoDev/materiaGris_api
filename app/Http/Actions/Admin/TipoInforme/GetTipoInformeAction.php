<?php

namespace App\Http\Actions\Admin\TipoInforme;

use App\Commands\Admin\TipoInforme\GetTipoInformeCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetTipoInformeAction
{
    public function __construct(
        private GetTipoInformeCommand $command,
    ) {}

    public function __invoke(Request $request, $id): JsonResponse
    {
        try {
            $template = $this->command->execute((int) $id);
            if (! $template) {
                return response()->json(['message' => 'Tipo de informe no encontrado'], 404);
            }
            return response()->json($template);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('GetTipoInformeAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
