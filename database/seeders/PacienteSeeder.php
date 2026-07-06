<?php

namespace Database\Seeders;

use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('app.env') === 'local' && Paciente::count() < 100) {
            Paciente::factory()->count(30)->create();
        }

        // Manually add patient with dates to test the alert for the start of a window
        $promotor = User::whereHas('roles', function ($query) {
            $query->where('name', User::ROL_PROMOTOR);
        })->inRandomOrder()->first();

        $user = User::factory()->create([
            'nombre' => "Pepijn van de Ven",
            'sexo' => 'M',
            'documento' => '17108984',
            'email' => 'pepijn@email.com',
        ]);
        $user->assign(User::ROL_PACIENTE);

        $baseDate = now()->subDays(59);

        $paciente1 = Paciente::create([
            'user_id' => $user->id,
            'nombre' => 'Pepijn',
            'apellido' => 'Van de Ven',
            'fecha_nacimiento' => '1985-06-15',
            'sexo' => 'M',
            'documento' => '17108984',

            'telefono' => '555123456',
            'email' => 'pepijn@email.com',

            'direccion' => 'Calle Falsa 123',

            'fecha_enrolamiento' => $baseDate,
            'activo' => true,

            'test' => false,
            'promotor_id' => $promotor?->id ?? null,
        ]);

        $paciente1->update(['user_id' => $user->id]);

        Entrenamiento::create([
            'paciente_id' => $paciente1->id,
            'fecha' => $baseDate,
            'seguimiento_dos_semanas_inicio' => $baseDate->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDate->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDate->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDate->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDate->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDate->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDate->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDate->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDate->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDate->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDate->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDate->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDate->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDate->copy()->addDays(182),

            'activo' => true,
            'user_id' => $promotor->id,
        ]);

        Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(35),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        // Manually add patient with dates to test the alert for the end of a window
        $user = User::factory()->create([
            'nombre' => "Pepijn End Window",
            'sexo' => 'M',
            'documento' => '17108985',
            'email' => 'pepijn2@email.com',
        ]);
        $user->assign(User::ROL_PACIENTE);

        $baseDate = now()->subDays(63);

        $paciente1 = Paciente::create([
            'user_id' => $user->id,
            'nombre' => 'Pino',
            'apellido' => 'End Window',
            'fecha_nacimiento' => '1985-06-15',
            'sexo' => 'M',
            'documento' => '17108985',

            'telefono' => '555123456',
            'email' => 'pepijn2@email.com',

            'direccion' => 'Calle Falsa 123',

            'fecha_enrolamiento' => $baseDate,
            'activo' => true,

            'test' => false,
            'promotor_id' => $promotor?->id ?? null,
        ]);

        $paciente1->update(['user_id' => $user->id]);

        Entrenamiento::create([
            'paciente_id' => $paciente1->id,
            'fecha' => $baseDate,
            'seguimiento_dos_semanas_inicio' => $baseDate->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDate->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDate->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDate->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDate->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDate->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDate->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDate->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDate->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDate->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDate->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDate->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDate->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDate->copy()->addDays(182),

            'activo' => true,
            'user_id' => $promotor->id,
        ]);

        Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $paciente1->id,
            'user_id' => $promotor->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(35),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

    }
}
