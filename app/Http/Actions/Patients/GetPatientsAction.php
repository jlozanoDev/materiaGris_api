<?php

namespace App\Http\Actions\Patients;

use App\Commands\Admin\GetPatientsCommand;
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
        return $this->command->execute($request);
    }

    public function __invoke(Request $request)
    {
        $result = $this->execute($request);
        return response()->json($result);
    }
}
