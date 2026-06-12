<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\SignReportCommand;
use App\Http\Requests\Reports\SignReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SignReportAction
{
    public function __construct(
        private SignReportCommand $command,
    ) {}

    public function __invoke(SignReportRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $report = $this->command->execute($id, $validated);
            return response()->json($report);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('SignReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
