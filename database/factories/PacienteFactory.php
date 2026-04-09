<?php

namespace Database\Factories;

use App\Models\Alerta;
use App\Models\Diagnostico;
use App\Models\Glucosa;
use App\Models\Paciente;
use App\Models\Paquete;
use App\Models\PresionArterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Paciente>
 */
class PacienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sexo = fake()->randomElement(['M', 'F']);
        $nombre = fake()->firstName($sexo === 'M' ? 'male' : 'female');
        $apellido = fake()->lastName;
        $email = Str::lower(Str::replace(' ', '.', $nombre) . '.' . Str::replace(' ', '.', $apellido)) . '@correo.com';
        if (Paciente::where('email', $email)->exists()) {
            $email = fake()->unique()->email;
        }
        $promotor = User::whereHas('roles', function ($query) {
            $query->where('name', User::ROL_PROMOTOR);
        })->inRandomOrder()->first();

        return [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-30 years')->format('Y-m-d'),
            'sexo' => $sexo,
            'documento' => fake()->unique()->numerify('########'),
            'telefono' => fake()->phoneNumber,
            'telefono2' => fake()->optional()->phoneNumber,
            'email' => $email,
            'direccion' => fake()->address,
            'referencia' => fake()->optional()->sentence,
            'centro_salud_id' => $promotor->centro_salud_id,
            'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => fake()->dateTimeBetween('-1 years'),
            'paquete_id' => Paquete::inRandomOrder()->value('id') ?? null,
            'activo' => fake()->boolean(90),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Paciente $paciente) {
            if (User::where('email', $paciente->email)->exists()) {
                $paciente->update(['email' => fake()->unique()->email]);
                $paciente->refresh();
            }

            $user = User::factory()->create([
                'nombre' => "$paciente->nombre $paciente->apellido",
                'sexo' => $paciente->sexo,
                'documento' => $paciente->documento,
                'email' => $paciente->email,
            ]);
            $user->assign(User::ROL_PACIENTE);

            $paciente->update(['user_id' => $user->id]);

            $diagnosticoId = Diagnostico::inRandomOrder()->value('id');
            $paciente->diagnosticos()->attach($diagnosticoId);

            switch ($diagnosticoId) {
                case 1:
                    $registros = Glucosa::factory()
                        ->count(rand(1, 5))
                        ->state(fn () => [
                            'fecha_registro' => fake()->dateTimeBetween($paciente->fecha_enrolamiento),
                        ])
                        ->create([
                        'paciente_id' => $paciente->id,
                    ]);

                    foreach ($registros as $registro) {
                        if ($registro->ayuno && $registro->valor > 125 || !$registro->ayuno && $registro->valor > 200) {
                            Alerta::create([
                                'paciente_id' => $paciente->id,
                                'tipo' => 'clinica',
                                'entidad_id' => $registro->id,
                                'entidad_tipo' => 'glucosa',
                                'descripcion' => 'Glucosa elevada / alta',
                                'fecha' => $registro->fecha_registro->format('Y-m-d'),
                                'fecha_contacto' => date('Y-m-d', strtotime($registro->fecha_registro . ' +1 day')),
                                'recomendacion' => 'Indicar que vaya a consulta',
                            ]);
                        }
                    }

                    break;
                case 2:
                    $registros = PresionArterial::factory()
                        ->count(rand(1, 5))
                        ->state(fn () => [
                            'fecha_registro' => fake()->dateTimeBetween($paciente->fecha_enrolamiento),
                        ])
                        ->create([
                        'paciente_id' => $paciente->id,
                    ]);

                    foreach ($registros as $registro) {
                        if ($registro->presion_sistolica > 140 || $registro->presion_diastolica > 90) {
                            Alerta::create([
                                'paciente_id' => $paciente->id,
                                'tipo' => 'clinica',
                                'entidad_id' => $registro->id,
                                'entidad_tipo' => 'presion_arterial',
                                'descripcion' => 'Presión arterial elevada / alta',
                                'fecha' => $registro->fecha_registro->format('Y-m-d'),
                                'fecha_contacto' => date('Y-m-d', strtotime($registro->fecha_registro . ' +1 day')),
                                'recomendacion' => 'Indicar que vaya a consulta',
                            ]);
                        }
                    }

                    break;
                case 3:
                    break;
            }

            $crearAlertaOperativa = fake()->boolean(80);
            if ($crearAlertaOperativa) {
                $cantidadAlertas = rand(1, 3);

                for ($i = 0; $i < $cantidadAlertas; $i++) {
                    Alerta::create([
                        'paciente_id' => $paciente->id,
                        'tipo' => 'operativa',
                        'entidad_id' => null,
                        'entidad_tipo' => null,
                        'descripcion' => fake()->randomElement([
                            'No se conecta',
                            'Problemas de conexión',
                            'Pedido de ayuda',
                        ]),
                        'fecha' => fake()->dateTimeBetween($paciente->fecha_enrolamiento)->format('Y-m-d'),
                        'fecha_contacto' => fake()->dateTimeBetween('now', '+15 days')->format('Y-m-d'),
                        'recomendacion' => 'Contactar y registrar',
                    ]);
                }
            }
        });
    }
}
