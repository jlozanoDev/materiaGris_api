<?php

namespace App\Http\Actions\Admin\Clinic;

use App\Commands\Admin\Clinic\GetClinicCommand;
use Illuminate\Http\JsonResponse;

class GetClinicAction
{
    private GetClinicCommand $command;

    public function __construct(GetClinicCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(): JsonResponse
    {
        try {
            $clinic = $this->command->execute();
            return response()->json($clinic);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Clinic not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
