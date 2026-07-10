<?php

namespace App\Repositories\Clinic;

use App\Models\Clinic;

class ClinicSaveRepository
{
    public function getOrFail(): Clinic
    {
        return Clinic::firstOrFail();
    }

    public function update(array $data): Clinic
    {
        $clinic = Clinic::first() ?? new Clinic();
        $clinic->fill($data);
        $clinic->save();
        return $clinic->fresh();
    }
}
