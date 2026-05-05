<?php

namespace App\Commands\Admin;

use App\Repositories\Patient\SavePatientRepository;
use App\Models\Patient;

class UpdatePatientCommand
{
    public function __construct(private SavePatientRepository $repo) {}

    public function execute($id, array $data): Patient
    {
        return $this->repo->actualizar($id, $data);
    }
}
