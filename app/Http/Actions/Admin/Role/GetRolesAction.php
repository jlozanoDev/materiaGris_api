<?php

namespace App\Http\Actions\Admin\Role;

use App\Commands\Admin\Role\GetRolesCommand;
use Illuminate\Http\JsonResponse;

class GetRolesAction
{
    private GetRolesCommand $command;

    public function __construct(GetRolesCommand $command)
    {
        $this->command = $command;
    }

    public function execute(): array
    {
        return $this->command->execute()->toArray();
    }

    public function __invoke()
    {
        return response()->json($this->execute());
    }
}
