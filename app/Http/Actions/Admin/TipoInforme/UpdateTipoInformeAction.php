<?php

namespace App\Http\Actions\Admin\TipoInforme;

use App\Commands\Admin\TipoInforme\UpdateTipoInformeCommand;
use App\Http\Requests\Admin\TipoInforme\UpdateTipoInformeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UpdateTipoInformeAction
{
    public function __construct(
        private UpdateTipoInformeCommand $command,
    ) {}

    public function __invoke(UpdateTipoInformeRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $template = $this->command->execute((int) $id, $validated);
            return response()->json($template);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('UpdateTipoInformeAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
