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

            $superadmin->assign('superadmin');
        }
    }
}
