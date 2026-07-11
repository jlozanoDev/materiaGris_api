<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\DeleteReportCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeleteReportAction
{
    public function __construct(
        private DeleteReportCommand $command,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $this->command->execute($id);
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Informe no encontrado'], 404);
        } catch (PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('DeleteReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
