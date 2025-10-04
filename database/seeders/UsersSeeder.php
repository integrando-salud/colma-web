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
            $superadmin = User::firstOrCreate([
                'email' => 'superadmin@correo.com'
            ], [
                'name' => 'Usuario Super Admin',
                'password' => 123456,
                'pais_id' => 1
            ]);

            $supervisor = User::firstOrCreate([
                'email' => 'supervisor@correo.com'
            ], [
                'name' => 'Usuario Supervisor',
                'password' => 123456,
                'pais_id' => 2
            ]);

            $supervisorPais = User::firstOrCreate([
                'email' => 'supervisorpais@correo.com'
            ], [
                'name' => 'Usuario Supervisor País',
                'password' => 123456,
                'pais_id' => 2
            ]);

            $promotor = User::firstOrCreate([
                'email' => 'promotor@correo.com'
            ], [
                'name' => 'Usuario Promotor',
                'password' => 123456,
                'pais_id' => 1,
                'centro_salud_id' => 1,
                'supervisor_id' => $supervisor->id
            ]);

            $superadmin->assign('superadmin');
            $supervisor->assign('supervisor');
            $supervisorPais->assign('supervisor_pais');
            $promotor->assign('promotor');
        }
    }
}
