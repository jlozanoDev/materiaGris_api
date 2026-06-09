<?php

namespace App\Repositories\ReportTemplate;

use App\Models\ReportTemplate;
use App\Models\PatientReport;

class ReportTemplateSaveRepository
{
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

    public function eliminar(int $id): void
    {
        $template = ReportTemplate::find($id);
        if (! $template) {
            throw new \RuntimeException('Tipo de informe no encontrado');
        }

        $hasReports = PatientReport::where('template_id', $id)->exists();
        if ($hasReports) {
            throw new \RuntimeException('No se puede eliminar: hay informes de pacientes que usan este tipo de informe.');
        }

        $template->delete();
    }
}
