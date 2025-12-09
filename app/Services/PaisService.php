<?php

namespace App\Services;

use App\Models\Pais;

class PaisService
{
    public function queryGetPaises(array $filters = [])
    {
        $query = Pais::query();

        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'asc');

        return $query->orderBy($orderBy, $orderDir);
    }
}
