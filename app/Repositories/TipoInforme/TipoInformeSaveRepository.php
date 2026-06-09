<?php

namespace App\Repositories\TipoInforme;

use App\Models\ReportTemplate;
use App\Models\PatientReport;

class TipoInformeSaveRepository
{
    /**
     * Create a new report template.
     *
     * @param array $data
     * @return ReportTemplate
     * @throws \RuntimeException when uniqueness constraints fail
     */
    public function crear(array $data): ReportTemplate
    {
        if (! empty($data['name'])) {
            $exists = ReportTemplate::where('name', $data['name'])->exists();
            if ($exists) {
                throw new \RuntimeException('Ya existe un tipo de informe con ese nombre.');
            }
        }

        if (isset($data['id'])) {
            unset($data['id']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return ReportTemplate::create($data);
    }

    /**
     * Update an existing report template by ID.
     * Validates uniqueness excluding the current template.
     *
     * @param int $id
     * @param array $data
     * @return ReportTemplate
     * @throws \RuntimeException when template not found or uniqueness fails
     */
    public function actualizar(int $id, array $data): ReportTemplate
    {
        $template = ReportTemplate::find($id);
        if (! $template) {
            throw new \RuntimeException('Tipo de informe no encontrado');
        }

        if (! empty($data['name'])) {
            $exists = ReportTemplate::where('name', $data['name'])
                ->where('id', '!=', $template->id)
                ->exists();
            if ($exists) {
                throw new \RuntimeException('Ya existe un tipo de informe con ese nombre.');
            }
        }

        if (isset($data['id'])) {
            unset($data['id']);
        }

        $template->fill($data);
        $template->save();

        return $template;
    }

    /**
     * Soft delete a template by ID.
     * Throws RuntimeException if template has active patient reports referencing it.
     *
     * @param int $id
     * @return void
     * @throws \RuntimeException when template not found or has referenced reports
     */
    public function eliminar(int $id): void
    {
        $template = ReportTemplate::find($id);
        if (! $template) {
            throw new \RuntimeException('Tipo de informe no encontrado');
        }

        // Check for existing patient reports referencing this template
        $hasReports = PatientReport::where('template_id', $id)->exists();
        if ($hasReports) {
            throw new \RuntimeException('No se puede eliminar: hay informes de pacientes que usan este tipo de informe.');
        }

        $template->delete();
    }
}
