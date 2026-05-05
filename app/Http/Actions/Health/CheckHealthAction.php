<?php

namespace App\Http\Actions\Health;

use App\Commands\Health\CheckHealthCommand;
use App\Models\HealthStatus;

class CheckHealthAction
{
    private CheckHealthCommand $command;

    public function __construct(CheckHealthCommand $command)
    {
        $this->command = $command;
    }

    public function execute(): HealthStatus
    {
        return $this->command->execute();
    }

    public function __invoke(): array
    {
        return $this->execute()->toArray();
    }
}
