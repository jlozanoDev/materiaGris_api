<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\CloseReportCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CloseReportAction
{
    public function __construct(
        private CloseReportCommand $command,
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $report = $this->command->execute($id);
            return response()->json($report);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('CloseReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
