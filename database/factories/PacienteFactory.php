<?php

namespace Database\Factories;

use App\Models\Alerta;
use App\Models\Diagnostico;
use App\Models\Glucosa;
use App\Models\Paciente;
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
        $nombre = fake('es_ES')->firstName($sexo === 'M' ? 'male' : 'female');
        $apellido = fake('es_ES')->lastName;
        $documento = fake()->unique()->numerify('########');
        $email = (Str::slug("$nombre $apellido $documento")) . '@correo.com';
        $promotor = User::whereHas('roles', function ($query) {
            $query->where('name', User::ROL_PROMOTOR);
        })->inRandomOrder()->first();

        return [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-30 years')->format('Y-m-d'),
            'sexo' => $sexo,
            'documento' => $documento,
            'telefono' => fake()->phoneNumber,
            'telefono2' => fake()->optional()->phoneNumber,
            'email' => $email,
            'direccion' => fake('es_ES')->address,
            'referencia' => fake('es_ES')->optional()->sentence,
            'centro_salud_id' => $promotor?->centro_salud_id ?? null,
            'promotor_id' => $promotor?->id ?? null,
            'fecha_enrolamiento' => fake()->dateTimeBetween('-1 years'),
            'activo' => fake()->boolean(90),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Paciente $paciente) {
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
                        ->state(function () use ($paciente) {
                            $fecha = fake()->dateTimeBetween($paciente->fecha_enrolamiento);

                            return [
                                'fecha_registro' => $fecha,
                                'fecha_usuario' => $fecha,
                            ];
                        })
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
                        ->state(function () use ($paciente) {
                            $fecha = fake()->dateTimeBetween($paciente->fecha_enrolamiento);

                            return [
                                'fecha_registro' => $fecha,
                                'fecha_usuario' => $fecha,
                            ];
                        })
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
                            'No completa las sesiones',
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
