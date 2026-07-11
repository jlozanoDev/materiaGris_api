<?php

namespace App\Http\Actions\Admin\Clinic;

use App\Commands\Admin\Clinic\UpdateClinicCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateClinicAction
{
    public function __construct(
        private UpdateClinicCommand $command,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'direccion' => 'sometimes|string|max:500',
                'telefono' => 'sometimes|string|max:50',
                'email' => 'sometimes|string|email|max:255',
                'ciudad' => 'sometimes|string|max:255',
                'provincia' => 'sometimes|string|max:255',
                'codigo_postal' => 'sometimes|string|max:20',
                'web' => 'sometimes|string|url|max:255',
                'cuit' => 'sometimes|string|max:20',
            ]);

            $clinic = $this->command->execute($data);
            return response()->json($clinic);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Clinic not found'], 404);
        } catch (\Exception $e) {
            Log::error('UpdateClinicAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
