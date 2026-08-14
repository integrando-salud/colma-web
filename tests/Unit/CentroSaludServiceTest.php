<?php

namespace Tests\Unit;

use App\Services\CentroSaludService;
use Tests\TestCase;

class CentroSaludServiceTest extends TestCase
{
    public function test_applies_the_active_filter_and_requested_order(): void
    {
        $query = (new CentroSaludService)->queryGetCentrosSalud([
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'desc',
        ])->getQuery();

        $this->assertSame('activo', $query->wheres[0]['column']);
        $this->assertTrue($query->wheres[0]['value']);
        $this->assertSame('nombre', $query->orders[0]['column']);
        $this->assertSame('desc', $query->orders[0]['direction']);
    }

    public function test_falls_back_to_safe_default_order_values(): void
    {
        $query = (new CentroSaludService)->queryGetCentrosSalud([
            'orderBy' => 'nombre; drop table centros_salud',
            'orderDir' => 'sideways',
        ])->getQuery();

        $this->assertSame('id', $query->orders[0]['column']);
        $this->assertSame('asc', $query->orders[0]['direction']);
    }
}
