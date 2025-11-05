<?php

namespace Database\Seeders;

use App\Models\Diagnostico;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiagnosticosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Diagnostico::firstOrCreate([
            'nombre' => 'Diabetes',
        ]);

        Diagnostico::firstOrCreate([
            'nombre' => 'Hipertensión arterial',
        ]);

        Diagnostico::firstOrCreate([
            'nombre' => 'Depresión',
        ]);
    }
}
