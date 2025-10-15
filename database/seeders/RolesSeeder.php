<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bouncer::role()->firstOrCreate([
            'name' => 'superusuario',
            'title' => 'Superusuario',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'supervisor',
            'title' => 'Supervisor',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'supervisor_pais',
            'title' => 'Supervisor País',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'promotor',
            'title' => 'Promotor',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'paciente',
            'title' => 'Paciente',
        ]);
    }
}
