<?php

namespace App\Services;

use App\Models\CentroSalud;
use Illuminate\Database\Eloquent\Builder;

class CentroSaludService
{
    /**
     * Columnas de centros de salud que pueden utilizarse para ordenar resultados.
     *
     * Esta lista evita incorporar nombres de columna arbitrarios en la consulta.
     */
    private const ORDERABLE_COLUMNS = ['id', 'nombre', 'pais_id', 'activo'];

    /**
     * Construye una consulta de centros de salud aplicando los filtros solicitados.
     *
     * La consulta se devuelve sin ejecutarla para que quien la use pueda añadir
     * condiciones, paginarla o recuperar sus resultados según lo necesite.
     * Solo se admiten las columnas definidas en {@see self::ORDERABLE_COLUMNS}
     * para ordenar; una columna no permitida se reemplaza por `id`.
     *
     * @param  array{activo?: bool|int|string|null, orderBy?: string, orderDir?: string}  $filters
     * @return Builder<CentroSalud>
     */
    public function queryGetCentrosSalud(array $filters = []): Builder
    {
        $query = CentroSalud::query();

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderBy = in_array($orderBy, self::ORDERABLE_COLUMNS, true) ? $orderBy : 'id';

        $orderDir = data_get($filters, 'orderDir', 'asc');
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'asc';

        return $query->orderBy($orderBy, $orderDir);
    }
}
