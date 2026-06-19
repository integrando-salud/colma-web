<?php

namespace Tests\Feature;

use App\Console\Commands\AppointmentAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class ProcessAppointmentMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $paciente1;
    protected $paciente2;


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
        ]);    }


    /**
     * Test that Interaccions with contactado do not result in a warning
     */
    public function test_contactado()
    {

        // Appointment in the past and completed
        Interaccion::create([
            'id' => 1,
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now()->subDays(4),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->subDays(3),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment in the past and completed
        Interaccion::create([
            'id' => 2,
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now(),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->subDays(1),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment in the past and completed
        Interaccion::create([
            'id' => 3,
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now()->subDays(4),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->subDays(3),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment today and completed
        Interaccion::create([
            'id' => 4,
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now()->subDays(4),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now(),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
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

        // Appointment in the past and completed
        Interaccion::create([
            'id' => 1,
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now()->subDays(4),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->subDays(3),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment in the future and beyond the warning range
        Interaccion::create([
            'id' => 2,
            'paciente_id' => $this->paciente1->id,
            'user_id' => $this->user1->id,
            'contactado' => false,
            'fecha' => now(),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->addDays(AppointmentAlerts::class::DAYS_FOR_WARNING+3),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment in the past and completed
        Interaccion::create([
            'id' => 3,
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => true,
            'fecha' => now()->subDays(4),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->subDays(3),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);

        // Appointment in the future and within the warning range
        Interaccion::create([
            'id' => 4,
            'paciente_id' => $this->paciente2->id,
            'user_id' => $this->user1->id,
            'contactado' => false,
            'fecha' => now(),
            'hora' => now()->setTime(14, 30),
            'fecha_cita' => now()->addDays(AppointmentAlerts::class::DAYS_FOR_WARNING-1),
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => Interaccion::MOTIVOS_CONTACTO[0],
            'motivo_reprogramacion' => Interaccion::MOTIVOS_REPROGRAMACION[0],
        ]);


        $this->artisan('colma:raise_appointment_alerts', [
            '--chunk' => 500,
            '--dry-run' => false,
        ])->assertExitCode(0);


        $this->assertDatabaseCount('alertas', 1);

        $this->assertDatabaseHas('alertas', [
            'paciente_id'=>2,
            'tipo'=>'supervisory',
            'entidad_tipo'=>'appointment',
            'entidad_id' => 4,
            'descripcion'=>'Appointment nearing due date',
            'recomendacion'=>'Contact CHW to plan appointment before the due date',
        ]);

    }


}


