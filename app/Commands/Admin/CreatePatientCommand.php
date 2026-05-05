<?php

namespace App\Commands\Admin;

use App\Repositories\Patient\SavePatientRepository;
use App\Models\Patient;

class CreatePatientCommand
{
    public function __construct(private SavePatientRepository $repo) {}

    public function execute(array $data): Patient
    {
        return $this->repo->crear($data);
    }
}
