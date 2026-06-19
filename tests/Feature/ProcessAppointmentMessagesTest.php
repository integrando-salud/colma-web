<?php

namespace Tests\Feature;

use App\Console\Commands\AppointmentAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Entrenamiento;
use Carbon\Carbon;


class ProcessAppointmentMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $paciente1;
    protected $paciente2;
    protected $baseDate;


    protected function setUp(): void
    {
        parent::setUp();

        // Injecting all data here rather than in Factory to limit impact on existing code
        $this->user1 = User::create([
            'nombre' => 'Juan Pérez',
            'sexo' => 'M',
            'documento' => '12345678',
            'email' => 'juan.perez@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->paciente1 = Paciente::create([
            'user_id' => 1,

            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
            'fecha_nacimiento' => '1985-06-15',
            'sexo' => 'M',
            'documento' => '12345678',

            'telefono' => '555123456',
            'email' => 'carlos.gomez@example.com',

            'direccion' => 'Calle Falsa 123',

            'fecha_enrolamiento' => now(),
            'activo' => true,

            'clave' => 'abc123',
            'test' => false,
        ]);

        $this->paciente2 = Paciente::create([
            'user_id' => 1,

            'nombre' => 'Carla',
            'apellido' => 'Gómez',
            'fecha_nacimiento' => '1985-07-15',
            'sexo' => 'F',
            'documento' => '87654321',

            'telefono' => '555123456',
            'email' => 'carla@example.com',

            'direccion' => 'Calle Falsa 123',

            'fecha_enrolamiento' => now(),
            'activo' => true,

            'clave' => 'abc123',
            'test' => false,
        ]);

        // Date for patient who should have done two monthly sessions
        $baseDate = now()->subDays(70);
        Entrenamiento::create([
            'paciente_id' => $this->paciente1->id,
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
            'user_id' => $this->user1->id,
        ]);

        // patient how should have done 5 monthly meetings
        $baseDate = now()->subDays(154);

        Entrenamiento::create([
            'paciente_id' => $this->paciente2->id,
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
            'user_id' => $this->user1->id,
        ]);


    }


    /**
     * Test that Interaccions with contactado do not result in a warning
     */
    public function test_contactado()
    {

        $baseDate = now()->subDays(70);

        Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(7),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(16),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas',
            'motivo_reprogramacion' => null,
        ]);

        Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(18),
            'hora' => now()->setTime(11, 0),
            'fecha_cita' => $baseDate->copy()->addDays(30),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Llamada mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(31),
            'hora' => now()->setTime(11, 0),
            'fecha_cita' => $baseDate->copy()->addDays(62),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Llamada mensual 2',
            'motivo_reprogramacion' => null,
        ]);



        $this->artisan('colma:raise_appointment_alerts', [
            '--chunk' => 500,
            '--dry-run' => false,
        ])->assertExitCode(0);


        $this->assertDatabaseCount('alertas', 0);

    }

    /*
     * Test that the no contactado entries result in a warning (with some contacto entries sprinkled in)
     */
    public function test_no_contactado()
    {

        $baseDate = now()->subDays(22);

        Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => false,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);



        $this->artisan('colma:raise_appointment_alerts', [
            '--chunk' => 500,
            '--dry-run' => false,
        ])->assertExitCode(0);


        $this->assertDatabaseCount('alertas', 1);

        $this->assertDatabaseHas('alertas', [
            'paciente_id'=>1,
            'tipo'=>'supervisory',
            'entidad_tipo'=>'appointment',
            'entidad_id' => $res->id,
            'descripcion'=>'Appointment due date is in the past',
            'recomendacion'=>'Skip appointment and schedule next',
        ]);

    }


}


