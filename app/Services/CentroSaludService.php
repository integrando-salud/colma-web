<?php

namespace App\Services;

use App\Models\CentroSalud;

class CentroSaludService
{
    public function queryGetCentrosSalud(array $filters = [])
    {
        $query = CentroSalud::query();

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->orderBy(data_get($filters, 'orderBy', 'id'), data_get($filters, 'orderDir', 'asc'));
    }
}
