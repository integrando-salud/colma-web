<?php

namespace Tests\Unit;

use App\Services\DiagnosticoService;
use Tests\TestCase;

class DiagnosticoServiceTest extends TestCase
{
    public function test_applies_the_active_filter_and_requested_order(): void
    {
        $query = (new DiagnosticoService)->queryGetDiagnosticos([
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
        $query = (new DiagnosticoService)->queryGetDiagnosticos([
            'orderBy' => 'nombre; drop table diagnosticos',
            'orderDir' => 'sideways',
        ])->getQuery();

        $this->assertSame('id', $query->orders[0]['column']);
        $this->assertSame('asc', $query->orders[0]['direction']);
    }
}
