<?php

namespace App\Http\Actions\Admin\ReportTemplate;

use App\Commands\Admin\ReportTemplate\DeleteReportTemplateCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeleteReportTemplateAction
{
    public function __construct(
        private DeleteReportTemplateCommand $command,
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
            if (str_contains($message, 'informes de pacientes')) {
                return response()->json(['message' => $message], 409);
            }
            return response()->json(['message' => $message], 404);
        } catch (\Exception $e) {
            Log::error('DeleteReportTemplateAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
