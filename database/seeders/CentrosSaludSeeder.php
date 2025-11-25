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
        if (config('app.env') === 'local') {
            CentroSalud::firstOrCreate([
                'id' => 1
            ], [
                'nombre' => 'Centro de Salud 1',
                'pais_id' => 1
            ]);

            CentroSalud::firstOrCreate([
                'id' => 2
            ], [
                'nombre' => 'Centro de Salud 2',
                'pais_id' => 2
            ]);

            CentroSalud::firstOrCreate([
                'id' => 3
            ], [
                'nombre' => 'Centro de Salud 3',
                'pais_id' => 2
            ]);

            CentroSalud::firstOrCreate([
                'id' => 4
            ], [
                'nombre' => 'Centro de Salud 4',
                'pais_id' => 1
            ]);
        }
    }
}
