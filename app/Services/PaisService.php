<?php

namespace App\Services;

use App\Models\Pais;
use Illuminate\Database\Eloquent\Builder;

class PaisService
{
    /**
     * Columnas de países que pueden utilizarse para ordenar resultados.
     *
     * Esta lista evita incorporar nombres de columna arbitrarios en la consulta.
     */
    private const array ORDERABLE_COLUMNS = ['id', 'nombre', 'codigo', 'activo'];

    /**
     * Construye una consulta de países aplicando los filtros solicitados.
     *
     * La consulta se devuelve sin ejecutarla para que quien la use pueda añadir
     * condiciones, paginarla o recuperar sus resultados según lo necesite.
     * Solo se admiten las columnas definidas en {@see self::ORDERABLE_COLUMNS}
     * para ordenar; una columna no permitida se reemplaza por `id`.
     *
     * @param  array{id?: int|string|null, activo?: bool|int|string|null, orderBy?: string, orderDir?: string}  $filters
     * @return Builder<Pais>
     */
    public function queryGetPaises(array $filters = []): Builder
    {
        $query = Pais::query();

        if (isset($filters['id'])) {
            $query->where('id', $filters['id']);
        }

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
