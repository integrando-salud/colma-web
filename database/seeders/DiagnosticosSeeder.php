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
            'name' => 'Diabetes',
        ]);

        Diagnostico::firstOrCreate([
            'name' => 'Hipertensión arterial',
        ]);

        Diagnostico::firstOrCreate([
            'name' => 'Depresión',
        ]);
    }
}
