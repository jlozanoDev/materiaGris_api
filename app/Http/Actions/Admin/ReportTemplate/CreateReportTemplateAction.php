<?php

namespace App\Http\Actions\Admin\ReportTemplate;

use App\Commands\Admin\ReportTemplate\CreateReportTemplateCommand;
use App\Http\Requests\Admin\ReportTemplate\CreateReportTemplateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CreateReportTemplateAction
{
    public function __construct(
        private CreateReportTemplateCommand $command,
    ) {}

    public function __invoke(CreateReportTemplateRequest $request): JsonResponse
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
            Log::error('CreateReportTemplateAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
