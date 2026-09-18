<?php

namespace Tests\Feature;

use App\Models\Entrenamiento;
use App\Models\Meta;
use App\Models\Paciente;
use App\Models\ProgresoElemento;
use App\Models\RespuestaQuiz;
use App\Models\TratamientoElemento;
use App\Models\TratamientoVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatosFuncionalesTratamientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_y_restaura_respuestas_quiz_metas_y_progreso_sin_mezclar_responsabilidades(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        [$entrenamiento, $quiz, $metas] = $this->crearContexto($paciente);

        $this->sincronizar($user, [
            'quiz' => [$this->payloadQuiz($quiz->id)],
            'metas' => [
                $this->payloadMeta($metas->id, 1001, true),
                $this->payloadMeta($metas->id, 1002, false),
            ],
            'progreso_elementos' => [
                $this->payloadProgreso($quiz->id, true),
                $this->payloadProgreso($metas->id, false),
            ],
        ]);

        $this->assertDatabaseCount('respuestas_quiz', 1);
        $this->assertDatabaseCount('metas', 2);
        $this->assertDatabaseHas('respuestas_quiz', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $quiz->id,
        ]);
        $this->assertDatabaseHas('metas', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $metas->id,
            'id_meta' => 1001,
        ]);
        $this->assertDatabaseHas('progreso_elementos', [
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $quiz->id,
            'completado' => 1,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertExactJson(['elementos_completados' => [$quiz->id]]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/datos-funcionales-tratamiento')
            ->assertOk()
            ->assertJsonPath('quiz.0.tratamiento_elemento_id', $quiz->id)
            ->assertJsonPath('quiz.0.preguntas.0.respuesta_usuario', 1)
            ->assertJsonPath('metas.0.tratamiento_elemento_id', $metas->id)
            ->assertJsonCount(2, 'metas');
    }

    public function test_rechaza_tipos_incorrectos_y_elementos_de_otra_version(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        [, $quiz, $metas, $descripcion] = $this->crearContexto($paciente);
        $otraVersion = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'DIABETES', 'hash' => str_repeat('b', 64)]);
        $quizAjeno = $otraVersion->sesiones()->create(['semana' => 1, 'sesion' => 1])
            ->elementos()->create(['tipo' => 'QUIZ', 'orden' => 0, 'contenido' => ['type' => 'QUIZ']]);

        $this->sincronizar($user, ['quiz' => [$this->payloadQuiz($descripcion->id)]], 422, 'quiz');
        $this->sincronizar($user, ['metas' => [$this->payloadMeta($quiz->id, 1001, true)]], 422, 'metas');
        $this->sincronizar($user, ['quiz' => [$this->payloadQuiz($quizAjeno->id)]], 422, 'quiz');
        $this->sincronizar($user, ['metas' => [$this->payloadMeta($metas->id, 1001, true)]], 200);
    }

    public function test_reintentos_y_eventos_fuera_de_orden_no_pisan_datos_funcionales_mas_nuevos(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        [$entrenamiento, $quiz, $metas] = $this->crearContexto($paciente);

        $nuevoQuiz = $this->payloadQuiz($quiz->id, '2026-08-20 11:30:00', 2);
        $nuevaMeta = $this->payloadMeta($metas->id, 1001, true, '2026-08-20 11:30:00', 'Nueva actividad');
        $this->sincronizar($user, ['quiz' => [$nuevoQuiz], 'metas' => [$nuevaMeta]]);
        $this->sincronizar($user, [
            'quiz' => [$this->payloadQuiz($quiz->id, '2026-08-20 10:30:00', 1)],
            'metas' => [$this->payloadMeta($metas->id, 1001, false, '2026-08-20 10:30:00', 'Actividad antigua')],
        ]);

        $this->assertSame(1, RespuestaQuiz::where('entrenamiento_id', $entrenamiento->id)->count());
        $this->assertSame(2, RespuestaQuiz::where('entrenamiento_id', $entrenamiento->id)->sole()->preguntas[0]['respuesta_usuario']);
        $meta = Meta::where('entrenamiento_id', $entrenamiento->id)->sole();
        $this->assertTrue($meta->meta_cumplida);
        $this->assertSame('Nueva actividad', $meta->actividad);
    }

    public function test_restaura_solo_el_estado_funcional_actual_de_las_metas(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        [, , $metas] = $this->crearContexto($paciente);

        $this->sincronizar($user, ['metas' => [$this->payloadMeta($metas->id, 1001, true, '2026-08-20 10:30:00')]]);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/datos-funcionales-tratamiento')
            ->assertJsonPath('metas.0.meta_cumplida', true);

        $this->sincronizar($user, ['metas' => [$this->payloadMeta($metas->id, 1001, false, '2026-08-20 11:30:00')]]);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/datos-funcionales-tratamiento')
            ->assertJsonPath('metas.0.meta_cumplida', false);

        $this->sincronizar($user, ['metas' => [$this->payloadMeta($metas->id, 1001, true, '2026-08-20 12:30:00')]]);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/datos-funcionales-tratamiento')
            ->assertJsonPath('metas.0.meta_cumplida', true);
    }

    public function test_aisla_los_datos_funcionales_por_paciente_y_entrenamiento(): void
    {
        [$userUno, $pacienteUno] = $this->crearPaciente();
        [$entrenamientoUno, $quiz, $metas] = $this->crearContexto($pacienteUno);
        [$userDos, $pacienteDos] = $this->crearPaciente();
        $entrenamientoDos = Entrenamiento::create(array_merge([
            'paciente_id' => $pacienteDos->id,
            'tratamiento_version_id' => $quiz->sesion->tratamiento_version_id,
            'fecha' => '2026-08-02',
            'activo' => true,
        ], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-02')));

        $this->sincronizar($userUno, ['quiz' => [$this->payloadQuiz($quiz->id)], 'metas' => [$this->payloadMeta($metas->id, 1001, true)]]);
        $this->sincronizar($userDos, ['quiz' => [$this->payloadQuiz($quiz->id, '2026-08-20 10:31:00', 2)], 'metas' => [$this->payloadMeta($metas->id, 1001, false, '2026-08-20 10:31:00')]]);

        $this->assertDatabaseCount('respuestas_quiz', 2);
        $this->assertDatabaseCount('metas', 2);
        $this->assertDatabaseHas('respuestas_quiz', ['paciente_id' => $pacienteUno->id, 'entrenamiento_id' => $entrenamientoUno->id, 'tratamiento_elemento_id' => $quiz->id]);
        $this->assertDatabaseHas('respuestas_quiz', ['paciente_id' => $pacienteDos->id, 'entrenamiento_id' => $entrenamientoDos->id, 'tratamiento_elemento_id' => $quiz->id]);
    }

    /** @return array{User, Paciente} */
    private function crearPaciente(): array
    {
        static $numero = 0;
        $numero++;
        $user = User::create([
            'nombre' => "Paciente $numero", 'sexo' => 'F', 'documento' => "DFT-U-$numero",
            'email' => "dft-$numero@example.test", 'password' => 'secreto',
        ]);
        $user->assign(User::ROL_PACIENTE);
        $paciente = Paciente::create([
            'user_id' => $user->id, 'nombre' => 'Paciente', 'apellido' => (string) $numero,
            'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F', 'documento' => "DFT-P-$numero",
            'telefono' => '123', 'email' => $user->email, 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '2026-01-01',
        ]);

        return [$user, $paciente];
    }

    /** @return array{Entrenamiento, TratamientoElemento, TratamientoElemento, TratamientoElemento} */
    private function crearContexto(Paciente $paciente): array
    {
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesion = $version->sesiones()->create(['semana' => 1, 'sesion' => 1]);
        $quiz = $sesion->elementos()->create(['tipo' => 'QUIZ', 'orden' => 0, 'contenido' => ['type' => 'QUIZ', 'questions' => []]]);
        $metas = $sesion->elementos()->create(['tipo' => 'METAS', 'orden' => 1, 'contenido' => ['type' => 'METAS', 'metasOptions' => []]]);
        $descripcion = $sesion->elementos()->create(['tipo' => 'DESCRIPTION', 'orden' => 2, 'contenido' => ['type' => 'DESCRIPTION']]);
        $entrenamiento = Entrenamiento::create(array_merge([
            'paciente_id' => $paciente->id, 'tratamiento_version_id' => $version->id, 'fecha' => '2026-08-01', 'activo' => true,
        ], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-01')));

        return [$entrenamiento, $quiz, $metas, $descripcion];
    }

    /** @return array<string, mixed> */
    private function payloadQuiz(int $elementoId, string $fecha = '2026-08-20 10:30:00', int $respuesta = 1): array
    {
        return [
            'tratamiento_elemento_id' => $elementoId,
            'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha,
            'preguntas' => [[
                'pregunta_index' => 0, 'pregunta_texto' => 'Pregunta', 'respuesta_usuario' => $respuesta,
                'respuesta_usuario_texto' => "Respuesta $respuesta", 'respuesta_correcta' => 2,
                'respuesta_correcta_texto' => 'Respuesta 2', 'es_correcta' => $respuesta === 2,
                'posibles_respuestas' => ['Respuesta 1', 'Respuesta 2'],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadMeta(int $elementoId, int $idMeta, bool $cumplida, string $fecha = '2026-08-20 10:30:00', string $actividad = 'Caminar'): array
    {
        return [
            'id_meta' => $idMeta, 'tratamiento_elemento_id' => $elementoId,
            'tipo_actividad' => 'Movimiento', 'actividad' => $actividad, 'lugar' => null, 'acompaniante' => null,
            'meta_cumplida' => $cumplida, 'motivo_incumplimiento' => $cumplida ? null : 'Sin tiempo',
            'fecha_registro' => $fecha, 'fecha_usuario' => $fecha, 'fecha_sincronizacion' => $fecha,
            'fecha_edicion' => null, 'fecha_objetivo' => '2026-08-25 10:30:00',
        ];
    }

    /** @return array<string, mixed> */
    private function payloadProgreso(int $elementoId, bool $completado): array
    {
        return ['tratamiento_elemento_id' => $elementoId, 'completado' => $completado, 'fecha_estado' => '2026-08-20 10:30:00', 'fecha_sincronizacion' => '2026-08-20 10:30:00'];
    }

    /** @param array<string, mixed> $data */
    private function sincronizar(User $user, array $data, int $status = 200, ?string $error = null): void
    {
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', ['userId' => $user->id, ...$data]);
        $response->assertStatus($status);
        if ($error) {
            $response->assertJsonValidationErrors($error);
        }
    }
}
