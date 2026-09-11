<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Http\Resources\Dashboard\PacienteListResource;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Models\Alerta;
use App\Models\Diagnostico;
use App\Models\Entrenamiento;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Interaccion;
use App\Models\Meta;
use App\Models\Paciente;
use App\Models\PresionArterial;
use App\Models\User;
use App\Services\PacienteService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PacienteServiceTest extends TestCase
{
    use RefreshDatabase;

    private PacienteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->service = app(PacienteService::class);
    }

    public function test_query_get_pacientes_filtra_y_conserva_la_fecha_proxima_al_ordenar(): void
    {
        $usuario = $this->crearUsuario('operador');
        $usuario->assign(User::ROL_SUPERUSUARIO);
        $this->actingAs($usuario);
        $paciente = $this->crearPaciente(['nombre' => 'Ana', 'apellido' => 'Paz']);
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-10']);

        $resultado = $this->service->queryGetPacientes([
            'nombre' => 'Ana', 'orderBy' => 'ingresos', 'orderDir' => 'ASC',
        ])->firstOrFail();

        $this->assertSame($paciente->id, $resultado->id);
        $this->assertSame('2026-03-10', substr((string) $resultado->nueva_fecha_contacto, 0, 10));
        $this->assertSame(
            $this->service->queryGetPacientes(['orderBy' => 'id; DROP TABLE pacientes', 'orderDir' => 'invalid'])->toSql(),
            $this->service->queryGetPacientes(['orderBy' => 'id_externo', 'orderDir' => 'desc'])->toSql(),
        );
    }

    public function test_store_paciente_crea_y_actualiza_el_paciente_usuario_y_diagnosticos(): void
    {
        $diagnostico = Diagnostico::create(['nombre' => 'Diabetes', 'activo' => true]);
        $datos = $this->datosPaciente(['diagnosticos' => [$diagnostico->id]]);

        $paciente = $this->service->storePaciente($datos);
        $this->assertSame('1990-02-01', $paciente->fecha_nacimiento->toDateString());
        $this->assertDatabaseHas('users', ['id' => $paciente->user_id, 'email' => $datos['email']]);
        $this->assertSame([$diagnostico->id], $paciente->diagnosticos->pluck('id')->all());

        $this->service->storePaciente(array_merge($datos, [
            'id' => $paciente->id, 'nombre' => 'Nombre actualizado', 'diagnosticos' => [], 'password' => null,
        ]));

        $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, 'nombre' => 'Nombre actualizado']);
        $this->assertCount(0, $paciente->fresh()->diagnosticos);
    }

    public function test_get_datos_clinicos_combina_mediciones_y_respeta_el_rango(): void
    {
        $paciente = $this->crearPaciente(['fecha_enrolamiento' => '2026-01-01']);
        Glucosa::create($this->datosGlucosa($paciente, '2026-02-01 08:00:00'));
        PresionArterial::create(array_merge(
            $this->datosPresion($paciente, '2026-02-02 09:00:00'),
            ['fecha_usuario' => '2026-01-15 13:05:00'],
        ));
        Glucosa::create($this->datosGlucosa($paciente, '2025-12-31 08:00:00'));

        $registros = $this->service->getDatosClinicos($paciente->id, 10, '01/02/2026', '02/02/2026');

        $this->assertSame(2, $registros->total());
        $this->assertSame('01/02/2026 08:00 AM', $registros->items()[0]['fecha']);
        $this->assertSame(90, $registros->items()[0]['glucosa_valor']);
        $this->assertSame('15/01/2026 01:05 PM', $registros->items()[1]['fecha']);
        $this->assertSame(110, $registros->items()[1]['presion_sistolica']);
    }

    public function test_cambiar_estado_alterna_el_valor_activo(): void
    {
        $paciente = $this->crearPaciente(['activo' => true]);
        $this->assertTrue($this->service->cambiarEstado($paciente));
        $this->assertFalse($paciente->fresh()->activo);
    }

    public function test_get_fechas_proximos_contactos_combina_y_formatea_las_fuentes(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-03', 'motivo' => MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS]);
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-02']);

        $fechas = $this->service->getFechasProximosContactos($paciente);

        $this->assertSame('02/03/2026', $fechas[0]['fecha']);
        $this->assertSame('Alerta clínica', $fechas[0]['motivo']);
        $this->assertSame('Seguimiento mensual 1', $fechas[1]['motivo']);
    }

    public function test_los_proximos_contactos_de_entrenamiento_muestran_la_siguiente_etapa(): void
    {
        $paciente = $this->crearPaciente();
        $entrenamiento = $this->crearEntrenamiento($paciente);

        $this->crearInteraccion($paciente, [
            'fecha' => '2026-01-01',
            'motivo' => MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO,
            'fecha_cita' => '2026-03-01',
        ]);
        $this->crearInteraccion($paciente, [
            'fecha' => '2026-01-02',
            'entrenamiento_id' => $entrenamiento->id,
            'motivo' => MotivoInteraccion::CITA_ENTRENAMIENTO,
            'nueva_fecha_contacto' => '2026-03-02',
        ]);
        $this->crearInteraccion($paciente, [
            'fecha' => '2026-01-03',
            'entrenamiento_id' => $entrenamiento->id,
            'motivo' => MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS,
            'nueva_fecha_contacto' => '2026-03-03',
        ]);

        foreach (range(1, 5) as $numero) {
            $this->crearInteraccion($paciente, [
                'fecha' => "2026-01-0".($numero + 3),
                'entrenamiento_id' => $entrenamiento->id,
                'motivo' => MotivoInteraccion::SEGUIMIENTO_MENSUAL,
                'nueva_fecha_contacto' => "2026-03-0".($numero + 3),
            ]);
        }

        $this->crearInteraccion($paciente, [
            'fecha' => '2026-01-09',
            'entrenamiento_id' => $entrenamiento->id,
            'motivo' => MotivoInteraccion::AGENDAR_CITA_FINAL,
            'fecha_cita' => '2026-03-09',
        ]);

        $motivos = collect($this->service->getFechasProximosContactos($paciente, 10))
            ->pluck('motivo')
            ->all();

        $this->assertSame([
            'Cita de entrenamiento',
            'Seguimiento 2 semanas luego del entrenamiento',
            'Seguimiento mensual 1',
            'Seguimiento mensual 2',
            'Seguimiento mensual 3',
            'Seguimiento mensual 4',
            'Seguimiento mensual 5',
            'Agendar cita final',
            'Cita final',
        ], $motivos);
    }

    public function test_los_proximos_contactos_del_piloto_finalizan_despues_del_tercer_seguimiento_mensual(): void
    {
        $paciente = $this->crearPaciente();
        $entrenamiento = $this->crearEntrenamiento($paciente, ['seguimientos_mensuales' => 3]);

        foreach (range(1, 3) as $numero) {
            $this->crearInteraccion($paciente, [
                'fecha' => "2026-01-0$numero",
                'entrenamiento_id' => $entrenamiento->id,
                'motivo' => MotivoInteraccion::SEGUIMIENTO_MENSUAL,
                'nueva_fecha_contacto' => "2026-03-0$numero",
            ]);
        }

        $motivos = collect($this->service->getFechasProximosContactos($paciente, 10))
            ->pluck('motivo')
            ->all();

        $this->assertSame([
            'Seguimiento mensual 2',
            'Seguimiento mensual 3',
            'Agendar cita final',
        ], $motivos);

        $this->assertSame(
            'Agendar cita final',
            MotivoInteraccion::SEGUIMIENTO_MENSUAL->siguienteMotivoContacto(3, $entrenamiento->seguimientos_mensuales),
        );
    }

    public function test_la_cita_de_entrenamiento_programada_se_usa_como_proximo_contacto(): void
    {
        $usuario = $this->crearUsuario('operador-citas');
        $usuario->assign(User::ROL_SUPERUSUARIO);
        $this->actingAs($usuario);
        $paciente = $this->crearPaciente();
        $this->crearInteraccion($paciente, [
            'contactado' => true,
            'fecha' => '2026-07-08',
            'motivo' => MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO,
            'fecha_cita' => '2026-07-09',
            'nueva_fecha_contacto' => '2026-07-22',
        ]);

        $this->assertSame('2026-07-09', substr((string) $this->service->getFechaProximoContacto($paciente), 0, 10));
        $this->assertSame('2026-07-09', substr((string) $this->service->queryGetPacientes()->firstOrFail()->nueva_fecha_contacto, 0, 10));

        $this->service->cargarRelacionesParaResource($paciente);
        $detalle = (new PacienteResource($paciente))->resolve();

        $this->assertSame('09/07/2026', $detalle['fecha_proximo_contacto']);
        $this->assertSame('09/07/2026', $detalle['proximos_contactos'][0]['fecha']);
        $this->assertSame('22/07/2026', $detalle['proximos_contactos'][1]['fecha']);
    }

    public function test_get_fechas_proximos_contactos_programados_excluye_las_ya_contactadas(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-01']);
        $this->crearInteraccion($paciente, ['contactado' => true, 'fecha' => '2026-03-02']);
        $pendiente = $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-04']);

        $fechas = $this->service->getFechasProximosContactosProgramados($paciente);

        $this->assertSame([$pendiente->nueva_fecha_contacto->toDateString()], $fechas->pluck('fecha')->map->toDateString()->all());
    }

    public function test_get_fechas_proximos_contactos_alertas_devuelve_solo_las_pendientes(): void
    {
        $paciente = $this->crearPaciente();
        $pendiente = $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-01', 'tipo' => 'operativa']);
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-02-01', 'revisado' => now()]);

        $fechas = $this->service->getFechasProximosContactosAlertas($paciente);

        $this->assertSame([$pendiente->id], $fechas->pluck('id')->all());
        $this->assertSame('Alerta operativa', $fechas->first()->motivo);
    }

    public function test_una_alerta_reprogramada_no_duplica_el_proximo_contacto_y_conserva_la_fecha_en_su_interaccion(): void
    {
        $paciente = $this->crearPaciente();
        $alerta = $this->crearAlerta($paciente, [
            'tipo' => 'operativa',
            'fecha_contacto' => '2026-03-03',
        ]);
        $interaccion = $this->crearInteraccion($paciente, [
            'alerta_id' => $alerta->id,
            'motivo' => MotivoInteraccion::PEDIDO_AYUDA,
            'nueva_fecha_contacto' => '2026-03-03',
        ]);

        $this->assertCount(0, $this->service->getFechasProximosContactosProgramados($paciente));
        $this->assertSame([
            ['fecha' => '03/03/2026', 'motivo' => 'Alerta operativa'],
        ], $this->service->getFechasProximosContactos($paciente));
        $this->assertSame('2026-03-03', $interaccion->fresh()->nueva_fecha_contacto?->toDateString());

        $this->service->cargarRelacionesParaResource($paciente);
        $detalle = (new PacienteResource($paciente))->resolve();

        $this->assertSame([
            ['fecha' => '03/03/2026', 'motivo' => 'Alerta operativa'],
        ], $detalle['proximos_contactos']);
    }

    public function test_get_fecha_proximo_contacto_elige_la_mas_cercana(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-03']);
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-02']);

        $this->assertSame('2026-03-02', substr((string) $this->service->getFechaProximoContacto($paciente), 0, 10));
    }

    public function test_resource_normaliza_la_fecha_y_limita_la_clave_al_listado(): void
    {
        $paciente = $this->crearPaciente(['clave' => Crypt::encryptString('clave-visible-en-modal')]);
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-02']);
        $this->service->cargarRelacionesParaResource($paciente);

        $detalle = (new PacienteResource($paciente))->resolve();
        $listado = (new PacienteListResource($paciente))->resolve();

        $this->assertSame('02/03/2026', $detalle['fecha_proximo_contacto']);
        $this->assertArrayNotHasKey('clave', $detalle);
        $this->assertSame('clave-visible-en-modal', $listado['clave']);
    }

    public function test_resource_cuenta_solo_las_metas_cumplidas_del_entrenamiento_activo(): void
    {
        $paciente = $this->crearPaciente();
        $entrenamientoActivo = $this->crearEntrenamiento($paciente);
        $entrenamientoInactivo = $this->crearEntrenamiento($paciente, ['activo' => false]);

        $this->crearMeta($paciente, $entrenamientoActivo, true);
        $this->crearMeta($paciente, $entrenamientoActivo, false);
        $this->crearMeta($paciente, $entrenamientoInactivo, true);

        $this->service->cargarRelacionesParaResource($paciente);
        $detalle = (new PacienteResource($paciente))->resolve();

        $this->assertSame(1, $detalle['metas_cumplidas']);
    }

    public function test_get_fecha_proximo_contacto_programado_devuelve_la_primera_pendiente(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-01']);
        $this->crearInteraccion($paciente, ['contactado' => true, 'fecha' => '2026-03-02']);
        $this->crearInteraccion($paciente, ['nueva_fecha_contacto' => '2026-03-04']);

        $this->assertSame('2026-03-04', substr((string) $this->service->getFechaProximoContactoProgramado($paciente), 0, 10));
    }

    public function test_get_fecha_proximo_contacto_alerta_devuelve_la_primera_pendiente(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-03']);
        $this->crearAlerta($paciente, ['fecha_contacto' => '2026-03-01']);

        $this->assertSame('2026-03-01', substr((string) $this->service->getFechaProximoContactoAlerta($paciente), 0, 10));
    }

    public function test_get_entrenamiento_actual_devuelve_el_ultimo_activo(): void
    {
        $paciente = $this->crearPaciente();
        $this->crearEntrenamiento($paciente, ['activo' => true, 'created_at' => '2026-01-01 00:00:00']);
        $actual = $this->crearEntrenamiento($paciente, ['activo' => true, 'created_at' => '2026-01-02 00:00:00']);
        $this->crearEntrenamiento($paciente, ['activo' => false, 'created_at' => '2026-01-03 00:00:00']);

        $this->assertSame($actual->id, $this->service->getEntrenamientoActual($paciente)?->id);
    }

    public function test_resetear_datos_paciente_test_elimina_sus_datos_y_rechaza_otros_pacientes(): void
    {
        $paciente = $this->crearPaciente(['test' => true]);
        Glucosa::create($this->datosGlucosa($paciente, '2026-01-01 08:00:00'));
        PresionArterial::create($this->datosPresion($paciente, '2026-01-01 08:00:00'));
        EstadoAnimo::create(array_merge(['paciente_id' => $paciente->id, 'valor' => 9, 'fecha_registro' => now(), 'fecha_usuario' => now(), 'fecha_sincronizacion' => now()], array_fill_keys(array_map(fn (int $numero) => "pregunta_$numero", range(1, 9)), 1)));
        $this->crearInteraccion($paciente);
        $this->crearAlerta($paciente);
        $this->crearEntrenamiento($paciente);

        $this->service->resetearDatosPacienteTest($paciente);
        $this->assertDatabaseCount('glucosa', 0);
        $this->assertSoftDeleted('interacciones', ['paciente_id' => $paciente->id]);
        $this->expectException(\Exception::class);
        $this->service->resetearDatosPacienteTest($this->crearPaciente(['test' => false]));
    }

    public function test_crear_paciente_test_generates_a_persisted_test_patient(): void
    {
        Diagnostico::create(['nombre' => 'Activo', 'activo' => true]);

        $paciente = $this->service->crearPacienteTest();

        $this->assertInstanceOf(Paciente::class, $paciente);
        $this->assertTrue($paciente->test);
        $this->assertSame('test-1', $paciente->documento);
    }

    private function crearUsuario(string $sufijo): User
    {
        return User::create(['nombre' => "Usuario $sufijo", 'sexo' => 'F', 'documento' => "USR-$sufijo", 'email' => "$sufijo@example.test", 'password' => 'secret']);
    }

    private function crearPaciente(array $atributos = []): Paciente
    {
        static $numero = 0;
        $numero++;

        return Paciente::create(array_merge([
            'nombre' => 'Paciente', 'apellido' => (string) $numero, 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F', 'documento' => "PAC-$numero", 'telefono' => '123456', 'email' => "paciente$numero@example.test", 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '2026-01-01', 'activo' => true,
        ], $atributos));
    }

    private function datosPaciente(array $atributos = []): array
    {
        return array_merge(['nombre' => 'Ana', 'apellido' => 'Paz', 'fecha_nacimiento' => '01/02/1990', 'sexo' => 'F', 'documento' => '12345678', 'telefono' => '123456', 'email' => 'ana@example.test', 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '03/04/2026', 'password' => 'secreto', 'diagnosticos' => []], $atributos);
    }

    private function crearInteraccion(Paciente $paciente, array $atributos = []): Interaccion
    {
        return Interaccion::create(array_merge(['paciente_id' => $paciente->id, 'user_id' => $this->crearUsuario(uniqid())->id, 'contactado' => false, 'fecha' => '2026-02-01', 'hora' => '09:00', 'modalidad' => 'Llamada por teléfono / WhatsApp', 'motivo' => MotivoInteraccion::SEGUIMIENTO_MENSUAL, 'nueva_fecha_contacto' => null], $atributos));
    }

    private function crearAlerta(Paciente $paciente, array $atributos = []): Alerta
    {
        return Alerta::create(array_merge(['paciente_id' => $paciente->id, 'tipo' => 'clinica', 'descripcion' => 'Alerta', 'fecha' => '2026-02-01', 'fecha_contacto' => null], $atributos));
    }

    private function crearEntrenamiento(Paciente $paciente, array $atributos = []): Entrenamiento
    {
        $entrenamiento = Entrenamiento::create(array_merge(['paciente_id' => $paciente->id, 'fecha' => '2026-01-01', 'activo' => true], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-01-01'), $atributos));

        if (isset($atributos['created_at'])) {
            $entrenamiento->created_at = $atributos['created_at'];
            $entrenamiento->save();
        }

        return $entrenamiento;
    }

    private function crearMeta(Paciente $paciente, Entrenamiento $entrenamiento, bool $metaCumplida): Meta
    {
        return Meta::create([
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'semana' => 1,
            'sesion' => 1,
            'tipo_actividad' => 'Caminata',
            'actividad' => 'Caminar 30 minutos',
            'lugar' => 'Parque',
            'meta_cumplida' => $metaCumplida,
            'fecha_registro' => '2026-01-01 08:00:00',
            'fecha_usuario' => '2026-01-01 08:00:00',
            'fecha_sincronizacion' => '2026-01-01 08:00:00',
            'fecha_objetivo' => '2026-01-02 08:00:00',
        ]);
    }

    private function datosGlucosa(Paciente $paciente, string $fecha): array
    {
        return ['paciente_id' => $paciente->id, 'valor_mgdl' => 90, 'ayuno' => true, 'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha];
    }

    private function datosPresion(Paciente $paciente, string $fecha): array
    {
        return ['paciente_id' => $paciente->id, 'presion_sistolica' => 110, 'presion_diastolica' => 70, 'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha];
    }
}
