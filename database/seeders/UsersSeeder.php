<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('app.env') === 'local') {
            $superusuario = User::firstOrCreate([
                'email' => 'superusuario@correo.com'
            ], [
                'nombre' => 'Carmen Díaz',
                'sexo' => 'F',
                'documento' => '12345678',
                'password' => 123456,
                'pais_id' => 1
            ]);

            $supervisorPais = User::firstOrCreate([
                'email' => 'supervisorpais@correo.com'
            ], [
                'nombre' => 'Jorge Ramos',
                'sexo' => 'M',
                'documento' => '12345',
                'password' => 123456,
                'pais_id' => 2
            ]);

            $supervisor = User::firstOrCreate([
                'email' => 'supervisor@correo.com'
            ], [
                'nombre' => 'Mónica Díaz',
                'sexo' => 'F',
                'documento' => '1234',
                'password' => 123456,
                'pais_id' => 1
            ]);

            $promotor = User::firstOrCreate([
                'email' => 'promotor@correo.com'
            ], [
                'nombre' => 'Juan Rossi',
                'sexo' => 'M',
                'documento' => '6789',
                'password' => 123456,
                'pais_id' => 1,
                'centro_salud_id' => 1,
                'supervisor_id' => $supervisor->id,
            ]);

            $superusuario->assign('superusuario');
            $supervisorPais->assign('supervisor_pais');
            $supervisor->assign('supervisor');
            $promotor->assign('promotor');
        }
    }
}
