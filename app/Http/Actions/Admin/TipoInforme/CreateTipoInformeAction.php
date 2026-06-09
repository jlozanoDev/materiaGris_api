<?php

namespace App\Http\Actions\Admin\TipoInforme;

use App\Commands\Admin\TipoInforme\CreateTipoInformeCommand;
use App\Http\Requests\Admin\TipoInforme\CreateTipoInformeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CreateTipoInformeAction
{
    public function __construct(
        private CreateTipoInformeCommand $command,
    ) {}

    public function __invoke(CreateTipoInformeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $template = $this->command->execute($validated);
            return response()->json($template, 201);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('CreateTipoInformeAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
