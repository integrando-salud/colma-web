<?php

namespace Tests\Unit;

use App\Services\PaisService;
use Tests\TestCase;

class PaisServiceTest extends TestCase
{
    public function test_applies_the_id_active_filters_and_requested_order(): void
    {
        $query = (new PaisService)->queryGetPaises([
            'id' => 15,
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'desc',
        ])->getQuery();

        $this->assertSame('id', $query->wheres[0]['column']);
        $this->assertSame(15, $query->wheres[0]['value']);
        $this->assertSame('activo', $query->wheres[1]['column']);
        $this->assertTrue($query->wheres[1]['value']);
        $this->assertSame('nombre', $query->orders[0]['column']);
        $this->assertSame('desc', $query->orders[0]['direction']);
    }

    public function test_falls_back_to_safe_default_order_values(): void
    {
        $query = (new PaisService)->queryGetPaises([
            'orderBy' => 'nombre; drop table paises',
            'orderDir' => 'sideways',
        ])->getQuery();

        $this->assertSame('id', $query->orders[0]['column']);
        $this->assertSame('asc', $query->orders[0]['direction']);
    }
}
