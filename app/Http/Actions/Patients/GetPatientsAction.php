<?php

namespace App\Http\Actions\Patients;

use App\Commands\Admin\Patient\GetPatientsCommand;
use Illuminate\Http\Request;

class GetPatientsAction
{
    private GetPatientsCommand $command;

    public function __construct(GetPatientsCommand $command)
    {
        $this->command = $command;
    }

    public function execute(Request $request)
    {
        return $this->command->execute($request->query());
    }

    public function __invoke(Request $request)
    {
        try {
            $result = $this->execute($request);
            return response()->json($result);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[GetPatientsAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
