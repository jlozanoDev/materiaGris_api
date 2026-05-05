<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\GetPermissionsCommand;

class GetPermissionsAction
{
    private GetPermissionsCommand $command;

    public function __construct(GetPermissionsCommand $command)
    {
        $this->command = $command;
    }

    public function execute(): array
    {
        return $this->command->execute()->toArray();
    }

    public function __invoke()
    {
        return $this->execute();
    }
}
