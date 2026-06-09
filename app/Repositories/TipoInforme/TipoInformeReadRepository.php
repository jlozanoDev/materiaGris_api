<?php

namespace App\Repositories\TipoInforme;

use App\Models\ReportTemplate;

class TipoInformeReadRepository
{
    /**
     * List templates with optional filters, excluding soft-deleted.
     *
     * @param array $filters Supported keys: is_active (bool|null), q (string search on name/description), per_page (int)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listar(array $filters = [])
    {
        $query = ReportTemplate::query();

        // Filter by active status
        if (array_key_exists('is_active', $filters)) {
            $val = $filters['is_active'];
            if ($val !== null && $val !== '' && $val !== 'all') {
                $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $query->where('is_active', $bool);
                }
            }
        }

        // Search by name or description
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

    /**
     * Find a single template by ID, excluding soft-deleted.
     *
     * @param int $id
     * @return ReportTemplate|null
     */
    public function buscarPorId(int $id): ?ReportTemplate
    {
        return ReportTemplate::find($id);
    }
}
