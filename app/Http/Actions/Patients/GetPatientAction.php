<?php

namespace App\Http\Actions\Patients;

use App\Commands\Admin\Patient\GetPatientCommand;
use App\Exceptions\PermissionDeniedException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GetPatientAction
{
    private GetPatientCommand $command;

    public function __construct(GetPatientCommand $command)
    {
        $this->command = $command;
    }

    public function execute(int $id): JsonResponse
    {
        $patient = $this->command->execute($id);

        if (! $patient) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $data = $patient->toArray();

        // Format dates as Y-m-d (remove time component)
        if ($patient->last_visit_at) {
            $data['last_visit_at'] = $patient->last_visit_at instanceof Carbon
                ? $patient->last_visit_at->format('Y-m-d')
                : Carbon::parse($patient->last_visit_at)->format('Y-m-d');
        } else {
            $data['last_visit_at'] = null;
        }

        if ($patient->date_of_birth) {
            $data['date_of_birth'] = $patient->date_of_birth instanceof Carbon
                ? $patient->date_of_birth->format('Y-m-d')
                : Carbon::parse($patient->date_of_birth)->format('Y-m-d');
        } else {
            $data['date_of_birth'] = null;
        }

        return response()->json($data);
    }

    public function __invoke(Request $request, $id): JsonResponse
    {
        try {
            return $this->execute((int) $id);
        } catch (PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('[GetPatientAction] ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }
}
