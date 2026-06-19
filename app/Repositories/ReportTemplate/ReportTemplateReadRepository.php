<?php

namespace App\Repositories\ReportTemplate;

use App\Models\ReportTemplate;

class ReportTemplateReadRepository
{
    public function listar(array $filters = [])
    {
        $query = ReportTemplate::query();

        if (array_key_exists('is_active', $filters)) {
            $val = $filters['is_active'];
            if ($val !== null && $val !== '' && $val !== 'all') {
                $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $query->where('is_active', $bool);
                }
            }
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->orderBy('name')->paginate($perPage);
    }

    public function listarActivas(): \Illuminate\Support\Collection
    {
        return ReportTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function buscarPorId(int $id): ?ReportTemplate
    {
        return ReportTemplate::find($id);
    }
}
