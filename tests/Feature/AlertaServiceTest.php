<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Paciente;
use App\Models\PresionArterial;
use App\Services\AlertaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertaServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlertaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AlertaService::class);
        config()->set('app.rpp', 10);
    }

    public function test_devuelve_las_recomendaciones_operativas_por_motivo(): void
    {
        $recomendaciones = $this->service->recomendacionesOperativas();

        $this->assertSame(
            "Contactar y explorar motivo del pedido de ayuda. Intentar dar solución.\nCompletar formulario de alerta operativa.",
            $recomendaciones[MotivoInteraccion::PEDIDO_AYUDA->value]
        );
        $this->assertSame(
            $recomendaciones[MotivoInteraccion::NO_COMPLETA_SESIONES->value],
            $recomendaciones[MotivoInteraccion::PROBLEMAS_CONEXION->value]
        );
    }

    public function test_calcula_la_fecha_de_contacto_operativa_sin_mutar_la_fecha_original(): void
    {
        $fecha = Carbon::parse('2026-04-10 15:30:00');

        $resultado = $this->service->fechaContactoOperativa($fecha);

        $this->assertSame('2026-04-13 15:30:00', $resultado->toDateTimeString());
        $this->assertSame('2026-04-10 15:30:00', $fecha->toDateTimeString());
    }

    public function test_obtiene_alertas_por_tipo_y_estado_con_sus_entidades_precargadas(): void
    {
        $paciente = $this->crearPaciente();
        $otroPaciente = $this->crearPaciente();
        $glucosa = $this->crearGlucosa($paciente);
        $presion = $this->crearPresion($paciente);

        $clinicaMasReciente = $this->crearAlerta($paciente, [
            'tipo' => 'clinica', 'entidad_tipo' => 'glucosa', 'entidad_id' => $glucosa->id, 'fecha' => '2026-05-03',
        ]);
        $clinicaRevisada = $this->crearAlerta($paciente, [
            'tipo' => 'clinica', 'entidad_tipo' => 'presion_arterial', 'entidad_id' => $presion->id, 'revisado' => now(),
        ]);
        $operativa = $this->crearAlerta($paciente, ['tipo' => 'operativa', 'fecha' => '2026-05-02']);
        $this->crearAlerta($otroPaciente, ['tipo' => 'clinica']);

        $pendientes = $this->service->getAlertasPaciente($paciente, true);
        $historial = $this->service->getAlertasPaciente($paciente, false);

        $this->assertSame([$clinicaMasReciente->id], $pendientes['clinicas']->pluck('id')->all());
        $this->assertSame([$operativa->id], $pendientes['operativas']->pluck('id')->all());
        $this->assertSame('page-clinicas-pendientes', $pendientes['clinicas']->getPageName());
        $this->assertTrue($pendientes['clinicas']->getCollection()->first()->relationLoaded('entidad'));
        $this->assertSame($glucosa->id, $pendientes['clinicas']->getCollection()->first()->entidad->id);
        $this->assertSame([$clinicaRevisada->id], $historial['clinicas']->pluck('id')->all());
        $this->assertSame('page-operativas', $historial['operativas']->getPageName());
    }

    public function test_carga_entidades_de_cada_tipo_y_descarta_las_de_otro_paciente(): void
    {
        $paciente = $this->crearPaciente();
        $otroPaciente = $this->crearPaciente();
        $glucosa = $this->crearGlucosa($paciente);
        $presion = $this->crearPresion($paciente);
        $estadoAnimo = $this->crearEstadoAnimo($paciente);
        $glucosaAjena = $this->crearGlucosa($otroPaciente);

        $alertas = collect([
            $this->crearAlerta($paciente, ['entidad_tipo' => 'glucosa', 'entidad_id' => $glucosa->id]),
            $this->crearAlerta($paciente, ['entidad_tipo' => 'presion_arterial', 'entidad_id' => $presion->id]),
            $this->crearAlerta($paciente, ['entidad_tipo' => 'estado_animo', 'entidad_id' => $estadoAnimo->id]),
            $this->crearAlerta($paciente, ['entidad_tipo' => 'glucosa', 'entidad_id' => $glucosaAjena->id]),
            $this->crearAlerta($paciente),
        ]);

        $this->service->cargarEntidades($alertas);

        $this->assertInstanceOf(Glucosa::class, $alertas[0]->entidad);
        $this->assertInstanceOf(PresionArterial::class, $alertas[1]->entidad);
        $this->assertInstanceOf(EstadoAnimo::class, $alertas[2]->entidad);
        $this->assertNull($alertas[3]->entidad);
        $this->assertNull($alertas[4]->entidad);
    }

    public function test_obtiene_la_entidad_y_reutiliza_la_relacion_precargada(): void
    {
        $paciente = $this->crearPaciente();
        $glucosa = $this->crearGlucosa($paciente);
        $alerta = $this->crearAlerta($paciente, ['entidad_tipo' => 'glucosa', 'entidad_id' => $glucosa->id]);

        $this->assertSame($glucosa->id, $this->service->getEntidad($alerta)->id);

        $alerta->setRelation('entidad', null);
        $this->assertNull($this->service->getEntidad($alerta));
        $this->assertNull($this->service->getEntidad($this->crearAlerta($paciente, ['entidad_tipo' => null, 'entidad_id' => null])));
    }

    public function test_registra_la_alerta_de_pedido_de_ayuda_con_su_fecha_y_recomendacion(): void
    {
        Carbon::setTestNow('2026-06-10 09:15:00');
        $paciente = $this->crearPaciente();

        $alerta = $this->service->savePedidoAyuda($paciente->id);

        $this->assertDatabaseHas('alertas', [
            'id' => $alerta->id,
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'descripcion' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'recomendacion' => $this->service->recomendacionesOperativas()[MotivoInteraccion::PEDIDO_AYUDA->value],
        ]);
        $this->assertSame('2026-06-10', $alerta->fecha->toDateString());
        $this->assertSame('2026-06-13', $alerta->fecha_contacto->toDateString());
    }

    private function crearPaciente(): Paciente
    {
        static $numero = 0;
        $numero++;

        return Paciente::create([
            'nombre' => 'Paciente', 'apellido' => (string) $numero, 'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F', 'documento' => "DOC-$numero", 'telefono' => '123456',
            'email' => "paciente$numero@example.test", 'direccion' => 'Calle 1',
            'fecha_enrolamiento' => '2026-01-01', 'activo' => true,
        ]);
    }

    private function crearAlerta(Paciente $paciente, array $atributos = []): Alerta
    {
        return Alerta::create(array_merge([
            'paciente_id' => $paciente->id, 'tipo' => 'clinica', 'descripcion' => 'Alerta de prueba', 'fecha' => '2026-05-01',
        ], $atributos));
    }

    private function crearGlucosa(Paciente $paciente): Glucosa
    {
        return Glucosa::create(['paciente_id' => $paciente->id, 'valor_mgdl' => 120, 'ayuno' => true, 'fecha_registro' => now(), 'fecha_usuario' => now(), 'fecha_sincronizacion' => now()]);
    }

    private function crearPresion(Paciente $paciente): PresionArterial
    {
        return PresionArterial::create(['paciente_id' => $paciente->id, 'presion_sistolica' => 130, 'presion_diastolica' => 80, 'fecha_registro' => now(), 'fecha_usuario' => now(), 'fecha_sincronizacion' => now()]);
    }

    private function crearEstadoAnimo(Paciente $paciente): EstadoAnimo
    {
        return EstadoAnimo::create(array_merge(['paciente_id' => $paciente->id, 'valor' => 8, 'fecha_registro' => now(), 'fecha_usuario' => now(), 'fecha_sincronizacion' => now()], array_fill_keys(array_map(fn ($n) => "pregunta_$n", range(1, 9)), 1)));
    }
}
