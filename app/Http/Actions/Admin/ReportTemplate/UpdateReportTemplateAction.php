<?php

namespace App\Http\Actions\Admin\ReportTemplate;

use App\Commands\Admin\ReportTemplate\UpdateReportTemplateCommand;
use App\Http\Requests\Admin\ReportTemplate\UpdateReportTemplateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UpdateReportTemplateAction
{
    public function __construct(
        private UpdateReportTemplateCommand $command,
    ) {}

    public function __invoke(UpdateReportTemplateRequest $request, $id): JsonResponse
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
            Log::error('UpdateReportTemplateAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
