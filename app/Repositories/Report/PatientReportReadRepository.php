<?php

namespace App\Repositories\Report;

use App\Models\PatientReport;

class PatientReportReadRepository
{
    public function listar(array $filters = [])
    {
        $query = PatientReport::with(['patient', 'user', 'template']);

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['patient_name'])) {
            $query->whereHas('patient', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['patient_name']}%")
                  ->orWhere('lastname', 'like', "%{$filters['patient_name']}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function buscarPorId(int $id): ?PatientReport
    {
        return PatientReport::with(['patient', 'user', 'template'])->find($id);
    }
}
