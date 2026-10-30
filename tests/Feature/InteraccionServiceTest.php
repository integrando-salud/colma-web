<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use App\Services\InteraccionService;
use App\Services\TratamientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class InteraccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private InteraccionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $tratamientoService = Mockery::mock(TratamientoService::class);
        $tratamientoService->shouldReceive('getVersionTratamientoPaciente')->andReturn(null);
        $this->service = new InteraccionService($tratamientoService);
        $this->actingAs($this->crearUsuario());
    }

    public function test_guarda_una_interaccion_y_actualiza_la_alerta_asociada(): void
    {
        $paciente = $this->crearPaciente();
        $alerta = $this->crearAlerta($paciente, ['revisado' => now(), 'fecha_contacto' => '2026-08-10']);

        $resultado = $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'alerta_id' => $alerta->id,
            'contactado' => false,
            'fecha' => '02/08/2026',
            'hora' => '10:30',
            'nueva_fecha_contacto' => '05/08/2026',
            'no_adherencia_pregunta_a' => ['Falta de tiempo'],
        ]));

        $this->assertTrue($resultado);
        $interaccion = Interaccion::firstOrFail();
        $this->assertSame($paciente->id, $interaccion->paciente_id);
        $this->assertSame(auth()->id(), $interaccion->user_id);
        $this->assertNull($interaccion->updated_by_user_id);
        $this->assertSame($alerta->id, $interaccion->alerta_id);
        $this->assertFalse($interaccion->contactado);
        $this->assertSame('2026-08-02', $interaccion->fecha->toDateString());
        $this->assertSame('10:30', $interaccion->hora->format('H:i'));
        $this->assertSame('2026-08-05', $interaccion->nueva_fecha_contacto->toDateString());

        $alerta->refresh();
        $this->assertNull($alerta->revisado);
        $this->assertSame('2026-08-05', $alerta->fecha_contacto->toDateString());
    }

    public function test_actualiza_una_interaccion_y_usa_la_fecha_y_hora_finales_en_una_cita_realizada(): void
    {
        $paciente = $this->crearPaciente();
        $this->service->storeInteraccion($this->datosInteraccion($paciente));
        $interaccion = Interaccion::firstOrFail();
        $creadorId = $interaccion->user_id;
        $editor = $this->crearUsuario();
        $this->actingAs($editor);

        $resultado = $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'id' => $interaccion->id,
            'contactado' => true,
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO->value,
            'fecha' => null,
            'hora' => null,
            'fecha_cita_final' => '09/08/2026',
            'hora_cita_final' => '14:45',
        ]));

        $this->assertTrue($resultado);
        $this->assertDatabaseCount('interacciones', 1);
        $interaccion->refresh();
        $this->assertTrue($interaccion->contactado);
        $this->assertSame($creadorId, $interaccion->user_id);
        $this->assertSame($editor->id, $interaccion->updated_by_user_id);
        $this->assertSame('2026-08-09', $interaccion->fecha->toDateString());
        $this->assertSame('14:45', $interaccion->hora->format('H:i'));
    }

    public function test_elimina_logicamente_una_interaccion(): void
    {
        $this->service->storeInteraccion($this->datosInteraccion($this->crearPaciente()));
        $interaccion = Interaccion::firstOrFail();

        $this->assertTrue($this->service->deleteInteraccion($interaccion));
        $this->assertSoftDeleted('interacciones', ['id' => $interaccion->id]);
    }

    public function test_elimina_el_entrenamiento_generado_al_eliminar_su_interaccion(): void
    {
        $paciente = $this->crearPaciente();
        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO->value,
        ]));
        $interaccion = Interaccion::firstOrFail();
        $entrenamientoId = $interaccion->entrenamiento_id;

        $this->assertNotNull($entrenamientoId);
        $this->assertTrue($this->service->deleteInteraccion($interaccion));

        $this->assertSoftDeleted('interacciones', ['id' => $interaccion->id]);
        $this->assertDatabaseMissing('entrenamientos', ['id' => $entrenamientoId]);
    }

    public function test_no_elimina_un_entrenamiento_preexistente_reagendado_por_la_interaccion(): void
    {
        $paciente = $this->crearPaciente();
        $this->service->guardarEntrenamiento($paciente->id, '2026-07-01');
        $entrenamiento = Entrenamiento::firstOrFail();

        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO->value,
            'id_entrenamiento' => $entrenamiento->id,
        ]));
        $interaccion = Interaccion::firstOrFail();

        $this->assertNull($interaccion->entrenamiento_id);
        $this->assertTrue($this->service->deleteInteraccion($interaccion));
        $this->assertDatabaseHas('entrenamientos', ['id' => $entrenamiento->id]);
    }

    public function test_crea_y_actualiza_el_cronograma_de_entrenamiento(): void
    {
        $paciente = $this->crearPaciente();

        $this->assertTrue($this->service->guardarEntrenamiento($paciente->id, '2026-08-01'));
        $entrenamiento = Entrenamiento::firstOrFail();

        $this->assertSame('2026-08-15', $entrenamiento->seguimiento_dos_semanas_inicio->toDateString());
        $this->assertSame('2027-01-30', $entrenamiento->agendar_cita_final_fin->toDateString());

        $this->assertTrue($this->service->guardarEntrenamiento($paciente->id, '2026-09-01', $entrenamiento->id));
        $entrenamiento->refresh();

        $this->assertSame('2026-09-15', $entrenamiento->seguimiento_dos_semanas_inicio->toDateString());
    }

    public function test_el_piloto_crea_un_entrenamiento_de_tres_seguimientos_sin_alterar_sus_sesiones(): void
    {
        config()->set('app.seguimientos_mensuales_nuevos', 3);
        $paciente = $this->crearPaciente();

        $this->assertTrue($this->service->guardarEntrenamiento($paciente->id, '2026-08-01'));
        $entrenamiento = Entrenamiento::firstOrFail();

        $this->assertSame(3, $entrenamiento->seguimientos_mensuales);
        $this->assertSame('2026-11-21', $entrenamiento->agendar_cita_final_inicio->toDateString());
        $this->assertSame('2026-12-05', $entrenamiento->agendar_cita_final_fin->toDateString());

        config()->set('app.seguimientos_mensuales_nuevos', 5);
        $this->assertTrue($this->service->guardarEntrenamiento($paciente->id, '2026-09-01', $entrenamiento->id));

        $this->assertSame(3, $entrenamiento->fresh()->seguimientos_mensuales);
    }

    public function test_crea_el_entrenamiento_al_registrar_una_cita_de_entrenamiento_contactada(): void
    {
        $paciente = $this->crearPaciente();

        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'motivo' => MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value,
            'fecha_cita' => '05/08/2026',
        ]));

        $this->assertDatabaseCount('entrenamientos', 0);

        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO->value,
            'fecha' => null,
            'hora' => null,
            'fecha_cita_final' => '09/08/2026',
            'hora_cita_final' => '14:45',
        ]));

        $entrenamiento = Entrenamiento::where('paciente_id', $paciente->id)->sole();

        $this->assertSame('2026-08-09', $entrenamiento->fecha->toDateString());
    }

    public function test_usa_la_fecha_de_la_cita_como_proximo_contacto_al_agendar_entrenamiento(): void
    {
        $paciente = $this->crearPaciente();

        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'motivo' => MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value,
            'fecha' => '14/09/2026',
            'fecha_cita' => '17/09/2026',
            'nueva_fecha_contacto' => '28/09/2026',
        ]));

        $this->assertSame(
            '2026-09-17',
            Interaccion::firstOrFail()->nueva_fecha_contacto?->toDateString(),
        );
    }

    public function test_no_crea_el_entrenamiento_al_registrar_una_cita_de_entrenamiento_no_contactada(): void
    {
        $paciente = $this->crearPaciente();

        $this->service->storeInteraccion($this->datosInteraccion($paciente, [
            'contactado' => false,
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO->value,
            'nueva_fecha_contacto' => '09/08/2026',
        ]));

        $this->assertDatabaseCount('entrenamientos', 0);
    }

    public function test_no_reasigna_un_entrenamiento_a_otro_paciente(): void
    {
        $paciente = $this->crearPaciente();
        $otroPaciente = $this->crearPaciente();
        $this->service->guardarEntrenamiento($paciente->id, '2026-08-01');

        $this->expectException(InvalidArgumentException::class);
        $this->service->guardarEntrenamiento($otroPaciente->id, '2026-08-02', Entrenamiento::firstOrFail()->id);
    }

    private function datosInteraccion(Paciente $paciente, array $atributos = []): array
    {
        return array_merge([
            'paciente_id' => $paciente->id,
            'contactado' => true,
            'fecha' => '01/08/2026',
            'hora' => '09:00',
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => MotivoInteraccion::SEGUIMIENTO_MENSUAL->value,
        ], $atributos);
    }

    private function crearUsuario(): User
    {
        static $numero = 0;
        $numero++;

        return User::create([
            'nombre' => 'Promotora de prueba',
            'sexo' => 'F',
            'documento' => "USER-$numero",
            'email' => "promotora$numero@example.test",
            'password' => 'password',
        ]);
    }

    private function crearPaciente(): Paciente
    {
        static $numero = 0;
        $numero++;

        return Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => (string) $numero,
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => "PAC-$numero",
            'telefono' => '123456',
            'email' => "paciente$numero@example.test",
            'direccion' => 'Calle de prueba',
            'fecha_enrolamiento' => '2026-01-01',
            'activo' => true,
        ]);
    }

    private function crearAlerta(Paciente $paciente, array $atributos = []): Alerta
    {
        return Alerta::create(array_merge([
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'descripcion' => 'Alerta de prueba',
            'fecha' => '2026-08-01',
        ], $atributos));
    }
}
