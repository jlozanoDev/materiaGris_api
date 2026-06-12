<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\InitReportCommand;
use App\Http\Requests\Reports\InitReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InitReportAction
{
    public function __construct(
        private InitReportCommand $command,
    ) {}

    public function __invoke(InitReportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $report = $this->command->execute($validated);
            return response()->json($report, 201);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('InitReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
