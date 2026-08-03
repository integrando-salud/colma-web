<?php

namespace Tests\Feature;

use App\Enums\AdvertenciaClinica;
use App\Models\Cuestionario;
use App\Models\Paciente;
use App\Models\PresionArterial;
use App\Models\User;
use App\Services\DatosClinicosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatosClinicosServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatosClinicosService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DatosClinicosService::class);
    }

    public function test_sincroniza_todos_los_tipos_de_datos_y_es_idempotente(): void
    {
        $paciente = $this->crearPacienteConUsuario();
        $data = $this->datosSincronizacion($paciente->user_id);

        $this->assertTrue($this->service->syncDatosApp($data));
        $this->assertDatabaseCount('glucosa', 1);
        $this->assertDatabaseCount('presion_arterial', 1);
        $this->assertDatabaseCount('estado_animo', 1);
        $this->assertDatabaseCount('cuestionarios', 1);
        $this->assertDatabaseCount('cuestionarios_preguntas', 1);
        $this->assertDatabaseCount('metas', 1);
        $this->assertDatabaseCount('multimedia_read', 1);
        $this->assertDatabaseCount('alertas', 2);
        $this->assertDatabaseHas('glucosa', ['paciente_id' => $paciente->id, 'id_app' => 101, 'valor_mgdl' => 65]);
        $this->assertDatabaseHas('estado_animo', ['paciente_id' => $paciente->id, 'id_app' => 301, 'valor' => 18]);
        $this->assertSame(2, Cuestionario::firstOrFail()->preguntas()->count());
        $this->assertNotNull($paciente->fresh()->fecha_ultima_sincronizacion);

        $data['glucosa'][0]['valor_mgdl'] = 68;
        $this->service->syncDatosApp($data);

        $this->assertDatabaseCount('glucosa', 1);
        $this->assertDatabaseCount('alertas', 2);
        $this->assertDatabaseHas('glucosa', ['paciente_id' => $paciente->id, 'id_app' => 101, 'valor_mgdl' => 68]);
        $this->assertDatabaseHas('metas', ['paciente_id' => $paciente->id, 'id_app' => 501, 'lugar' => null]);
    }

    public function test_devuelve_recomendaciones_de_glucosa_segun_el_umbral(): void
    {
        $this->assertNotNull($this->service->getRecomendacionGlucosa(69));
        $this->assertNotNull($this->service->getRecomendacionGlucosa(301));
        $this->assertNull($this->service->getRecomendacionGlucosa(70));
        $this->assertNull($this->service->getRecomendacionGlucosa(300));
    }

    public function test_devuelve_recomendaciones_de_presion_y_exige_cuatro_dias_elevados(): void
    {
        $paciente = $this->crearPacienteConUsuario();

        $this->assertNotNull($this->service->getRecomendacionPresionArterial(180, 80, $paciente->id));
        $this->assertNotNull($this->service->getRecomendacionPresionArterial(85, 80, $paciente->id));

        foreach (range(1, 4) as $dia) {
            $this->crearPresion($paciente, 140, 90, "2026-01-0$dia 08:00:00");
        }

        $this->assertSame(
            'Contactar y preguntar si pudo agendar un control con su médico.',
            $this->service->getRecomendacionPresionArterial(140, 90, $paciente->id)
        );
    }

    public function test_obtiene_las_ultimas_mediciones_de_presion_en_orden_descendente(): void
    {
        $paciente = $this->crearPacienteConUsuario();
        $this->crearPresion($paciente, 120, 80, '2026-01-01 08:00:00');
        $reciente = $this->crearPresion($paciente, 130, 85, '2026-01-03 08:00:00');
        $intermedio = $this->crearPresion($paciente, 125, 82, '2026-01-02 08:00:00');

        $registros = $this->service->getUltimosRegistrosPresionArterial($paciente->id, 2);

        $this->assertSame([$reciente->id, $intermedio->id], $registros->pluck('id')->all());
    }

    public function test_devuelve_recomendaciones_de_estado_de_animo_segun_el_puntaje(): void
    {
        $this->assertNotNull($this->service->getRecomendacionEstadoAnimo(10));
        $this->assertNotNull($this->service->getRecomendacionEstadoAnimo(15));
        $this->assertNull($this->service->getRecomendacionEstadoAnimo(9));
    }

    private function crearPacienteConUsuario(): Paciente
    {
        static $numero = 0;
        $numero++;
        $usuario = User::create([
            'nombre' => "Usuario $numero", 'sexo' => 'F', 'documento' => "USR-$numero",
            'email' => "usuario$numero@example.test", 'password' => 'secret',
        ]);

        return Paciente::create([
            'user_id' => $usuario->id, 'nombre' => 'Paciente', 'apellido' => (string) $numero,
            'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F', 'documento' => "PAC-$numero",
            'telefono' => '123456', 'email' => "paciente$numero@example.test", 'direccion' => 'Calle 1',
            'fecha_enrolamiento' => '2026-01-01', 'activo' => true,
        ]);
    }

    private function crearPresion(Paciente $paciente, int $sistolica, int $diastolica, string $fecha): PresionArterial
    {
        return PresionArterial::create([
            'paciente_id' => $paciente->id, 'presion_sistolica' => $sistolica, 'presion_diastolica' => $diastolica,
            'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha,
            'advertencia' => AdvertenciaClinica::ALTA,
        ]);
    }

    /** @return array<string, mixed> */
    private function datosSincronizacion(int $userId): array
    {
        $fecha = '2026-01-10 08:00:00';

        return [
            'userId' => $userId,
            'glucosa' => [[
                'id_glucosa' => 101, 'valor_mgdl' => 65, 'ayuno' => true, 'fecha_registro' => $fecha,
                'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha, 'advertencia' => AdvertenciaClinica::BAJA->value,
            ]],
            'presion' => [[
                'id_presion' => 201, 'sistolica' => 120, 'diastolica' => 80, 'fecha_registro' => $fecha,
                'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha, 'advertencia' => AdvertenciaClinica::NORMAL->value,
            ]],
            'estado_animo' => [array_merge([
                'id_estado_animo' => 301, 'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha,
            ], array_fill_keys(array_map(fn (int $n) => "pregunta_$n", range(1, 9)), 2))],
            'quiz' => [[
                'id_quiz' => 401, 'quiz_type' => 'hta', 'fecha_registro' => $fecha, 'fecha_usuario' => $fecha,
                'fecha_sincronizacion' => $fecha, 'preguntas' => [
                    ['pregunta_index' => 1, 'pregunta_texto' => 'Pregunta 1', 'respuesta_usuario' => 1, 'respuesta_usuario_texto' => 'Uno', 'respuesta_correcta' => 1, 'respuesta_correcta_texto' => 'Uno', 'es_correcta' => true, 'posibles_respuestas' => ['Uno', 'Dos']],
                    ['pregunta_index' => 2, 'pregunta_texto' => 'Pregunta 2', 'respuesta_usuario' => 2, 'respuesta_usuario_texto' => 'Dos', 'respuesta_correcta' => 1, 'respuesta_correcta_texto' => 'Uno', 'es_correcta' => false, 'posibles_respuestas' => ['Uno', 'Dos']],
                ],
            ]],
            'metas' => [[
                'id_meta' => 501, 'semana' => 1, 'sesion' => 2, 'tipo_actividad' => 'Caminar', 'actividad' => '30 minutos',
                'lugar' => null, 'acompaniante' => null, 'meta_cumplida' => false, 'motivo_incumplimiento' => null,
                'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha, 'fecha_edicion' => null, 'fecha_objetivo' => $fecha,
            ]],
            'multimedia_read' => [[
                'id_multimedia_read' => 601, 'treatment_id' => 'hta', 'week_id' => 1, 'session_id' => 2,
                'multimedia_type' => 'video', 'multimedia_unique_key' => 'video-1', 'fecha_registro' => $fecha, 'fecha_sincronizacion' => $fecha,
            ]],
        ];
    }
}
