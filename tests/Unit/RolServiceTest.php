<?php

namespace Tests\Unit;

use App\Services\RolService;
use Tests\TestCase;

class RolServiceTest extends TestCase
{
    public function test_applies_the_excluded_roles_filter_and_requested_order(): void
    {
        $query = (new RolService)->queryGetRoles([
            'excepto' => ['paciente', 'promotor'],
            'orderBy' => 'name',
            'orderDir' => 'desc',
        ])->getQuery();

        $this->assertSame('name', $query->wheres[0]['column']);
        $this->assertSame('NotIn', $query->wheres[0]['type']);
        $this->assertSame(['paciente', 'promotor'], $query->wheres[0]['values']);
        $this->assertSame('name', $query->orders[0]['column']);
        $this->assertSame('desc', $query->orders[0]['direction']);
    }

    public function test_accepts_a_single_role_to_exclude(): void
    {
        $query = (new RolService)->queryGetRoles(['excepto' => 'paciente'])->getQuery();

        $this->assertSame('name', $query->wheres[0]['column']);
        $this->assertSame('NotIn', $query->wheres[0]['type']);
        $this->assertSame(['paciente'], $query->wheres[0]['values']);
    }

    public function test_falls_back_to_safe_default_order_values(): void
    {
        $query = (new RolService)->queryGetRoles([
            'orderBy' => 'name; drop table roles',
            'orderDir' => 'sideways',
        ])->getQuery();

        $this->assertSame('id', $query->orders[0]['column']);
        $this->assertSame('asc', $query->orders[0]['direction']);
    }
}
