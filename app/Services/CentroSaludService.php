<?php

namespace App\Services;

use App\Models\CentroSalud;

class CentroSaludService
{
    public function queryGetCentrosSalud(array $filters = [])
    {
        $query = CentroSalud::query();

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'asc');

        return $query->orderBy($orderBy, $orderDir);
    }
}
