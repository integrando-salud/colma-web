<?php

namespace Database\Factories;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Glucosa>
 */
class GlucosaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ayuno = fake()->boolean();

        return [
            'paciente_id' => Paciente::inRandomOrder()->value('id'),
            'valor' => $ayuno
                ? fake()->numberBetween(70, 250)
                : fake()->numberBetween(90, 300),
            'ayuno' => $ayuno,
            'fecha_registro' => fake()->dateTimeBetween('-6 months', 'now'),
            'fecha_sincronizacion' => now(),
        ];
    }
}
