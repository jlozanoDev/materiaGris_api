<?php

namespace App\Models;

class HealthStatus
{
    public string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function toArray(): array
    {
        return ['status' => $this->status];
    }
}
