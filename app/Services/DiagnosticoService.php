<?php

namespace App\Services;

use App\Models\Diagnostico;

class DiagnosticoService
{
    public function queryGetDiagnosticos(array $filters = [])
    {
        $query = Diagnostico::query();

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'asc');

        return $query->orderBy($orderBy, $orderDir);
    }
}
