<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\ListReportsCommand;
use App\Http\Requests\Reports\ListReportsRequest;
use App\Http\Resources\PatientReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ListReportsAction
{
    public function __construct(
        private ListReportsCommand $command,
    ) {}

    public function __invoke(ListReportsRequest $request): JsonResponse
    {
        try {
            $filters = $request->only(['patient_id', 'status', 'patient', 'date_from', 'date_to', 'template_id', 'per_page']);
            $paginator = $this->command->execute($filters);
            return response()->json([
                'data' => PatientReportResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('ListReportsAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
