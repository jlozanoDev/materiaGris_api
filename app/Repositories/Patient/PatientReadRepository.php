<?php

namespace App\Repositories\Patient;

use App\Models\Patient;
use Carbon\Carbon;

class PatientReadRepository
{
    public function buscarPorId(int $id): ?Patient
    {
        return Patient::find($id);
    }


    public function buscarPorFiltros(array $filters = [])
    {
        $query = Patient::query();

        // By default return only active patients unless an explicit filter is provided.
        if (! array_key_exists('is_active', $filters)) {
            $query->where('is_active', true);
        } else {
            // If 'is_active' is provided, allow values: 'true'|'false'|true|false or 'all' to disable filtering
            $val = $filters['is_active'];
            if ($val !== 'all' && $val !== null && $val !== '') {
                $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $query->where('is_active', $bool);
                }
            }
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('second_last_name', 'like', "%{$q}%")
                    ->orWhere('national_id', 'like', "%{$q}%")
                    ->orWhere('medical_record_number', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (! empty($filters['insurance'])) {
            $aseg = $filters['insurance'];
            if (is_array($aseg)) {
                $query->whereIn('insurance_id', $aseg);
            } else {
                $query->where('insurance_id', $aseg);
            }
        }

        if (! empty($filters['age_min'])) {
            $ageMin = (int) $filters['age_min'];
            $fechaMax = Carbon::today()->subYears($ageMin)->endOfDay()->toDateString();
            $query->where('date_of_birth', '<=', $fechaMax);
        }

        if (! empty($filters['age_max'])) {
            $ageMax = (int) $filters['age_max'];
            $fechaMin = Carbon::today()->subYears($ageMax)->startOfDay()->toDateString();
            $query->where('date_of_birth', '>=', $fechaMin);
        }

        if (! empty($filters['registered_from']) || ! empty($filters['registered_to'])) {
            $from = ! empty($filters['registered_from']) ? Carbon::parse($filters['registered_from'])->startOfDay() : null;
            $to = ! empty($filters['registered_to']) ? Carbon::parse($filters['registered_to'])->endOfDay() : null;
            if ($from && $to) $query->whereBetween('created_at', [$from, $to]);
            elseif ($from) $query->where('created_at', '>=', $from);
            elseif ($to) $query->where('created_at', '<=', $to);
        }

        if (! empty($filters['last_visit_from']) || ! empty($filters['last_visit_to'])) {
            $from = ! empty($filters['last_visit_from']) ? Carbon::parse($filters['last_visit_from'])->startOfDay() : null;
            $to = ! empty($filters['last_visit_to']) ? Carbon::parse($filters['last_visit_to'])->endOfDay() : null;
            if ($from && $to) $query->whereBetween('last_visit_at', [$from, $to]);
            elseif ($from) $query->where('last_visit_at', '>=', $from);
            elseif ($to) $query->where('last_visit_at', '<=', $to);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }
}
