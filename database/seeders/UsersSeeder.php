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
                'nombre' => 'Super Usuario',
                'sexo' => 'M',
                'documento' => '12345678',
                'password' => 123456,
                'pais_id' => 1
            ]);

            $superusuario->assign('superusuario');
        }
    }
}
