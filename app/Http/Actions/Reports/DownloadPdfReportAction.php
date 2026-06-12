<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\DownloadPdfReportCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DownloadPdfReportAction
{
    public function __construct(
        private DownloadPdfReportCommand $command,
    ) {}

    public function __invoke(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        try {
            $result = $this->command->execute($id);
            return response()->download($result['path'], $result['filename'], [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('DownloadPdfReportAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
