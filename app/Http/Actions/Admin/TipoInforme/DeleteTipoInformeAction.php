<?php

namespace App\Http\Actions\Admin\TipoInforme;

use App\Commands\Admin\TipoInforme\DeleteTipoInformeCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeleteTipoInformeAction
{
    public function __construct(
        private DeleteTipoInformeCommand $command,
    ) {}

    public function __invoke(Request $request, $id): JsonResponse
    {
        try {
            $this->command->execute((int) $id);
            return response()->json(null, 204);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            // If the message mentions "informes de pacientes", it's a 409 conflict
            if (str_contains($message, 'informes de pacientes')) {
                return response()->json(['message' => $message], 409);
            }
            // Otherwise it's a "not found" → 404
            return response()->json(['message' => $message], 404);
        } catch (\Exception $e) {
            Log::error('DeleteTipoInformeAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
