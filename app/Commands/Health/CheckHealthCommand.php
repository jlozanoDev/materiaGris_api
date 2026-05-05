<?php

namespace App\Commands\Health;
use App\Models\HealthStatus;
use App\Repositories\HealthRepository;

class CheckHealthCommand
{
    private HealthRepository $repository;

    public function __construct(HealthRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): HealthStatus
    {
        return $this->repository->getStatus();
    }
}
