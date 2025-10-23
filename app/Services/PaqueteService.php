<?php

namespace App\Services;

use App\Models\Paquete;

class PaqueteService
{
    public function queryGetPaquetes(array $filters = [])
    {
        $query = Paquete::query();

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'asc');

        return $query->orderBy($orderBy, $orderDir);
    }
}
