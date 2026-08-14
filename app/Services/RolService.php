<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolService
{
    /**
     * Columnas de roles que pueden utilizarse para ordenar resultados.
     *
     * Esta lista evita incorporar nombres de columna arbitrarios en la consulta.
     */
    private const array ORDERABLE_COLUMNS = ['id', 'name', 'title', 'scope'];

    /**
     * Construye una consulta de roles aplicando los filtros solicitados.
     *
     * La consulta se devuelve sin ejecutarla para que quien la use pueda añadir
     * condiciones o recuperar sus resultados según lo necesite. Los roles
     * indicados en `excepto` se excluyen por su nombre. Solo se admiten las
     * columnas definidas en {@see self::ORDERABLE_COLUMNS} para ordenar; una
     * columna no permitida se reemplaza por `id` y una dirección inválida por
     * `asc`.
     *
     * @param  array{excepto?: string|array<int, string>, orderBy?: string, orderDir?: string}  $filters
     */
    public function queryGetRoles(array $filters = []): Builder
    {
        $query = Bouncer::role()->newQuery();

        if (isset($filters['excepto'])) {
            $query->whereNotIn('name', (array) $filters['excepto']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderBy = in_array($orderBy, self::ORDERABLE_COLUMNS, true) ? $orderBy : 'id';

        $orderDir = data_get($filters, 'orderDir', 'asc');
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'asc';

        return $query->orderBy($orderBy, $orderDir);
    }
}
