<?php

namespace Database\Seeders;

use App\Models\Alerta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateDescripcionAlertasOperativasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Alerta::where('descripcion', 'No completa sesiones')
            ->update(['descripcion' => 'Problemas de conexión']);
    }
}
