<?php

namespace App\Repositories\Patient;

use App\Models\Patient;

class SavePatientRepository
{
    /**
     * Create a new patient record.
     *
     * @param array $data
     * @return Patient
     * @throws \RuntimeException when uniqueness constraints fail
     */
    public function crear(array $data): Patient
    {
        if (! empty($data['national_id']) && Patient::where('national_id', $data['national_id'])->exists()) {
            throw new \RuntimeException('El dni ya existe.');
        }

        if (! empty($data['medical_record_number']) && Patient::where('medical_record_number', $data['medical_record_number'])->exists()) {
            throw new \RuntimeException('El número de historial médico ya existe.');
        }

        if (isset($data['id'])) {
            unset($data['id']);
        }
        $data['is_active'] = $data['is_active'] ?? true;

        return Patient::create($data);
    }

    /**
     * Update an existing patient record by id.
     * Validates uniqueness excluding the current patient.
     *
     * @param int|string $id
     * @param array $data
     * @return Patient
     * @throws \RuntimeException when uniqueness constraints fail
     */
    public function actualizar($id, array $data): Patient
    {
        $patient = Patient::find($id);
        if (! $patient) {
            throw new \RuntimeException('Paciente no encontrado');
        }

        if (! empty($data['national_id']) && Patient::where('national_id', $data['national_id'])->where('id', '!=', $patient->id)->exists()) {
            throw new \RuntimeException('El dni ya existe.');
        }

        if (! empty($data['medical_record_number']) && Patient::where('medical_record_number', $data['medical_record_number'])->where('id', '!=', $patient->id)->exists()) {
            throw new \RuntimeException('El número de historial médico ya existe.');
        }

        if (isset($data['id'])) {
            unset($data['id']);
        }

        $patient->fill($data);
        $patient->save();

        return $patient;
    }
}
