<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\GetUsersCommand;
use Illuminate\Http\JsonResponse;

class GetUsersAction
{
    private GetUsersCommand $command;

    public function __construct(GetUsersCommand $command)
    {
        $this->command = $command;
    }

    public function execute(): JsonResponse
    {
        return response()->json($this->command->execute());
    }

    public function __invoke()
    {
        return $this->execute();
    }
}
