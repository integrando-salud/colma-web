<?php

namespace Database\Factories;

use App\Models\CentroSalud;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        $sexo = fake()->randomElement(['M', 'F']);
        $nombre = fake()->firstName($sexo === 'M' ? 'male' : 'female') . ' ' . fake()->lastName;
        $email = Str::lower(Str::replace(' ', '.', $nombre)) . '@correo.com';
        $centroSalud = CentroSalud::inRandomOrder()->first();

        return [
            'nombre' => $nombre,
            'sexo' => $sexo,
            'documento' => fake()->unique()->numerify('########'),
            'email' => $email,
            'password' => Hash::make('123456'),
            'pais_id' => $centroSalud->pais_id,
            'centro_salud_id' => $centroSalud->id,
            'supervisor_id' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            $rol = fake()->randomElement([
                User::ROL_SUPERVISOR,
                User::ROL_SUPERVISOR_PAIS,
                User::ROL_PROMOTOR,
            ]);

            $user->assign($rol);
        });
    }
}
