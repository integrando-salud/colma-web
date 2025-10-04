<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CentrosSaludSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CentroSalud::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'Centro de Salud 1',
            'pais_id' => 1
        ]);

        CentroSalud::firstOrCreate([
            'id' => 2
        ], [
            'name' => 'Centro de Salud 2',
            'pais_id' => 2
        ]);

        CentroSalud::firstOrCreate([
            'id' => 3
        ], [
            'name' => 'Centro de Salud 3',
            'pais_id' => 2
        ]);
    }
}
