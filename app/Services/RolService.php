<?php

namespace App\Services;

use Silber\Bouncer\BouncerFacade as Bouncer;

class RolService
{
    public function queryGetRoles(array $filters = [])
    {
        $query = Bouncer::role()->newQuery();

        if (isset($filters['exclude'])) {
            $query->whereNotIn('name', (array) $filters['exclude']);
        }

        return $query->orderBy(data_get($filters, 'orderBy', 'id'), data_get($filters, 'orderDir', 'asc'));
    }
}
