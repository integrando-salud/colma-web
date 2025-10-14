<?php

namespace App\Services;

use App\Models\Pais;

class PaisService
{
    public function queryGetPaises(array $filters = [])
    {
        $query = Pais::query();

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->orderBy(data_get($filters, 'orderBy', 'id'), data_get($filters, 'orderDir', 'asc'));
    }
}
