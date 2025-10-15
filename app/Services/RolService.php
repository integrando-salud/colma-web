<?php

namespace App\Services;

use Silber\Bouncer\BouncerFacade as Bouncer;

class RolService
{
    public function queryGetRoles(array $filters = [])
    {
        $query = Bouncer::role()->newQuery();

        if (isset($filters['excepto'])) {
            $query->whereNotIn('name', (array) $filters['excepto']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'asc');

        return $query->orderBy($orderBy, $orderDir);
    }
}
