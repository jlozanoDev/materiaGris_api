<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\GetActiveTemplatesCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GetActiveTemplatesAction
{
    public function __construct(
        private GetActiveTemplatesCommand $command,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $templates = $this->command->execute();
            return response()->json([
                'data' => $templates,
            ]);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('GetActiveTemplatesAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
