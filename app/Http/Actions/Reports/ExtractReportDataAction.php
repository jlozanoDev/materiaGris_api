<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\ExtractReportDataCommand;
use App\Exceptions\LlmResponseException;
use App\Exceptions\LlmTimeoutException;
use App\Exceptions\LlmUnavailableException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TemplateNotFoundException;
use App\Http\Requests\Reports\ExtractReportDataRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ExtractReportDataAction
{
    public function __construct(
        private readonly ExtractReportDataCommand $command,
    ) {}

    public function __invoke(ExtractReportDataRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->command->execute(
                $id,
                $request->validated('transcript'),
                $request->validated('template_id'),
                auth()->user(),
            );

            return response()->json([
                'data' => $result,
                'meta' => (object) [],
                'message' => 'Datos extraídos correctamente',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Informe no encontrado',
            ], 404);
        } catch (PermissionDeniedException $e) {
            return response()->json([
                'message' => 'No tienes permisos',
            ], 403);
        } catch (TemplateNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Plantilla no válida',
            ], 400);
        } catch (LlmTimeoutException | LlmResponseException $e) {
            return response()->json([
                'message' => 'Error al procesar con IA',
            ], 500);
        } catch (LlmUnavailableException $e) {
            return response()->json([
                'message' => 'Servicio de IA temporalmente no disponible',
            ], 503);
        }
    }
}
