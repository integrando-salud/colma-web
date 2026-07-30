<?php

namespace Database\Factories;

use App\Enums\AdvertenciaClinica;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PresionArterial>
 */
class PresionArterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sistolica = fake()->numberBetween(90, 200);
        $diastolica = fake()->numberBetween(50, min(120, $sistolica - 10));
        $fechaRegistro = fake()->dateTimeBetween('-6 months');

        return [
            'paciente_id' => Paciente::inRandomOrder()->value('id'),
            'presion_sistolica' => $sistolica,
            'presion_diastolica' => $diastolica,
            'fecha_registro' => $fechaRegistro,
            'fecha_usuario' => $fechaRegistro,
            'fecha_sincronizacion' => now(),
            'advertencia' => fake()->randomElement(AdvertenciaClinica::cases())
        ];
    }
}
