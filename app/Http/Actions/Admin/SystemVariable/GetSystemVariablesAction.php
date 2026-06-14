<?php

namespace App\Http\Actions\Admin\SystemVariable;

use App\Commands\Admin\GetSystemVariablesCommand;

class GetSystemVariablesAction
{
    private GetSystemVariablesCommand $command;

    public function __construct(GetSystemVariablesCommand $command)
    {
        $this->command = $command;
    }

    public function execute(): array
    {
        return $this->command->execute();
    }

    public function __invoke()
    {
        return response()->json($this->execute());
    }
}
