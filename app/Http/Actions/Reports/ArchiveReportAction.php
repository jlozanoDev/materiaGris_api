<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\ArchiveReportCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArchiveReportAction
{
    public function __construct(
        private ArchiveReportCommand $command,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'pdf' => 'required|file|mimetypes:application/pdf|max:10240',
            ]);

            $report = $this->command->execute($id, $request->file('pdf'));
            return response()->json($report);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('ArchiveReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
