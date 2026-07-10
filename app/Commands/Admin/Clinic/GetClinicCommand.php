<?php

namespace App\Commands\Admin\Clinic;

use App\Models\Clinic;
use App\Repositories\Clinic\ClinicSaveRepository;

class GetClinicCommand
{
    public function __construct(
        private ClinicSaveRepository $repo,
    ) {}

    public function execute(): Clinic
    {
        return $this->repo->getOrFail();
    }
}
