<?php

namespace Tests\Feature;

use App\Console\Commands\AppointmentAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Alerta;
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
            'promotor_id' => $this->user1->id,
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
            'promotor_id' => $this->user1->id,
        ]);



        // patient who should have done 5 monthly meetings
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
     * Test that Interaccions with contactado do not result in a warning if next window not yet due
     */
    public function test_contactado()
    {

        // Date for patient who should have done two monthly sessions
        $baseDate = now()->subDays(55);
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
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
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
    public function test_warn_for_monthly2()
    {
        // Date for patient who should have done two monthly sessions
        $baseDate = now()->subDays(59);
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
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        $baseDatePat2 = now()->subDays(55);

        Entrenamiento::create([
            'paciente_id' => $this->paciente2->id,
            'fecha' => $baseDatePat2,
            'seguimiento_dos_semanas_inicio' => $baseDatePat2->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDatePat2->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDatePat2->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDatePat2->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDatePat2->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDatePat2->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDatePat2->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDatePat2->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDatePat2->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDatePat2->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDatePat2->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDatePat2->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDatePat2->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDatePat2->copy()->addDays(182),

            'activo' => true,
            'user_id' => $this->user1->id,
        ]);


        Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(19),
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
            'tipo'=>'calls',
            'entidad_tipo'=>'appointment',
            'descripcion'=>'Appointment Seguimiento mensual 2 can now be scheduled',
            'recomendacion'=>'You can now schedule appointment Seguimiento mensual 2',
        ]);

    }

    /*
     * Test that the no contactado entries do not result in warnings after the day the window has become active
     */
    public function test_warn_only_on_day()
    {
        // Date for patient who should have done two monthly sessions
        $baseDate = now()->subDays(54);
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
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        $baseDatePat2 = now()->subDays(55);

        Entrenamiento::create([
            'paciente_id' => $this->paciente2->id,
            'fecha' => $baseDatePat2,
            'seguimiento_dos_semanas_inicio' => $baseDatePat2->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDatePat2->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDatePat2->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDatePat2->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDatePat2->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDatePat2->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDatePat2->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDatePat2->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDatePat2->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDatePat2->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDatePat2->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDatePat2->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDatePat2->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDatePat2->copy()->addDays(182),

            'activo' => true,
            'user_id' => $this->user1->id,
        ]);


        Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
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
    public function test_no_warn_for_scheduled_monthly2()
    {
        // Date for patient who should have done two monthly sessions
        $baseDate = now()->subDays(59);
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
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => false,
            'fecha' => $baseDate->copy()->addDays(35),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(55),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 2',
            'motivo_reprogramacion' => null,
        ]);

        $baseDatePat2 = now()->subDays(55);

        Entrenamiento::create([
            'paciente_id' => $this->paciente2->id,
            'fecha' => $baseDatePat2,
            'seguimiento_dos_semanas_inicio' => $baseDatePat2->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDatePat2->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDatePat2->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDatePat2->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDatePat2->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDatePat2->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDatePat2->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDatePat2->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDatePat2->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDatePat2->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDatePat2->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDatePat2->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDatePat2->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDatePat2->copy()->addDays(182),

            'activo' => true,
            'user_id' => $this->user1->id,
        ]);


        Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);


        $this->artisan('colma:raise_appointment_alerts', [
            '--chunk' => 500,
            '--dry-run' => false,
        ])->assertExitCode(0);


        $this->assertDatabaseCount('alertas', 0);


    }

    /*
     * Test that an alert is raised when the window is near closing (currently 7 days before end of window)
     * and an appointment is not scheduled. This also checks that the previous reminder for the start of the
     * window is automatically cancelled by the system.
     */
    public function test_alert_for_window_close()
    {
        // Date for patient who should have done one monthly sessions and for whom monthly session 2 window is closing
        $baseDate = now()->subDays(63);
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
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDate->copy()->addDays(19),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDate->copy()->addDays(35),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento mensual 1',
            'motivo_reprogramacion' => null,
        ]);

        $baseDatePat2 = now()->subDays(55);

        Entrenamiento::create([
            'paciente_id' => $this->paciente2->id,
            'fecha' => $baseDatePat2,
            'seguimiento_dos_semanas_inicio' => $baseDatePat2->copy()->addDays(14),
            'seguimiento_dos_semanas_fin' => $baseDatePat2->copy()->addDays(21),

            'llamada_mensual_1_inicio' => $baseDatePat2->copy()->addDays(28),
            'llamada_mensual_1_fin' => $baseDatePat2->copy()->addDays(42),

            'llamada_mensual_2_inicio' => $baseDatePat2->copy()->addDays(56),
            'llamada_mensual_2_fin' => $baseDatePat2->copy()->addDays(70),

            'llamada_mensual_3_inicio' => $baseDatePat2->copy()->addDays(84),
            'llamada_mensual_3_fin' => $baseDatePat2->copy()->addDays(98),

            'llamada_mensual_4_inicio' => $baseDatePat2->copy()->addDays(112),
            'llamada_mensual_4_fin' => $baseDatePat2->copy()->addDays(126),

            'llamada_mensual_5_inicio' => $baseDatePat2->copy()->addDays(140),
            'llamada_mensual_5_fin' => $baseDatePat2->copy()->addDays(154),

            'agendar_cita_final_inicio' => $baseDatePat2->copy()->addDays(168),
            'agendar_cita_final_fin' => $baseDatePat2->copy()->addDays(182),

            'activo' => true,
            'user_id' => $this->user1->id,
        ]);


        Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(0),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Cita de entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        $res = Interaccion::create([
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => $baseDatePat2->copy()->addDays(0),
            'hora' => now()->setTime(10, 30),
            'fecha_cita' => $baseDatePat2->copy()->addDays(19),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
            'motivo_reprogramacion' => null,
        ]);

        Alerta::create([
            'paciente_id' => $this->paciente1->id,
            'fecha' => $baseDate->copy()->addDays(56),
            'user_id' => $this->user1->id,
            'tipo'=>'calls',
            'entidad_tipo'=>'appointment',
            'descripcion'=>'Appointment Seguimiento mensual 2 can now be scheduled',
            'recomendacion'=>'You can now schedule appointment Seguimiento mensual 2',
        ]);

        $this->assertDatabaseCount('alertas', 1);

        $this->assertDatabaseHas('alertas', [
            'paciente_id'=>$this->paciente1->id,
            'tipo'=>'calls',
            'entidad_tipo'=>'appointment'
        ]);

        $this->artisan('colma:raise_appointment_alerts', [
            '--chunk' => 500,
            '--dry-run' => false,
        ])->assertExitCode(0);


        $this->assertDatabaseCount('alertas', 2);

        $this->assertDatabaseHas('alertas', [
            'paciente_id'=>1,
            'tipo'=>'calls',
            'entidad_tipo'=>'window_end'
        ]);

        $this->assertDatabaseHas('alertas', [
            'paciente_id'=>1,
            'tipo'=>'calls',
            'entidad_tipo'=>'appointment',
            'recomendacion'=>"Canceled by the system upon window end warning"
        ]);


    }


}


