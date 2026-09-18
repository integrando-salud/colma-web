<?php

namespace Tests\Feature;

use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\ProgresoElemento;
use App\Models\TratamientoElemento;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPacienteControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que un paciente pueda sincronizar una carga mínima válida.
     */
    public function test_paciente_puede_sincronizar_sus_datos(): void
    {
        [$user, $paciente] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', ['userId' => $user->id])
            ->assertOk()
            ->assertJsonPath('message', 'Datos sincronizados con éxito.');

        $this->assertNotNull($paciente->fresh()->fecha_ultima_sincronizacion);
    }

    /**
     * Verifica que el progreso se registre contra el elemento lógico.
     */
    public function test_paciente_puede_sincronizar_elementos_completados(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesionUno = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $video = TratamientoElemento::create(['tratamiento_sesion_id' => $sesionUno->id, 'tipo' => 'VIDEO', 'orden' => 0, 'contenido' => ['type' => 'VIDEO']]);
        $quiz = TratamientoElemento::create(['tratamiento_sesion_id' => $sesionUno->id, 'tipo' => 'QUIZ', 'orden' => 1, 'contenido' => ['type' => 'QUIZ']]);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $fecha = '2026-08-20 10:30:00';

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', [
                'userId' => $user->id,
                'progreso_elementos' => [
                    [
                        'tratamiento_elemento_id' => $video->id,
                        'completado' => true,
                        'fecha_estado' => $fecha,
                        'fecha_sincronizacion' => $fecha,
                    ],
                    [
                        'tratamiento_elemento_id' => $quiz->id,
                        'completado' => true,
                        'fecha_estado' => $fecha,
                        'fecha_sincronizacion' => $fecha,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount('progreso_elementos', 2);
        $this->assertDatabaseHas('progreso_elementos', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $video->id,
            'completado' => 1,
        ]);
        $this->assertDatabaseHas('progreso_elementos', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $quiz->id,
            'completado' => 1,
        ]);
    }

    /**
     * Verifica que la aplicación recupere solo los contenidos completados del
     * entrenamiento activo, para restaurar su avance en otro dispositivo.
     */
    public function test_paciente_puede_obtener_los_elementos_completados_del_entrenamiento_actual(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesion = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $elementoAnterior = TratamientoElemento::create(['tratamiento_sesion_id' => $sesion->id, 'tipo' => 'VIDEO', 'orden' => 0, 'contenido' => ['type' => 'VIDEO']]);
        $elementoCompletado = TratamientoElemento::create(['tratamiento_sesion_id' => $sesion->id, 'tipo' => 'QUIZ', 'orden' => 1, 'contenido' => ['type' => 'QUIZ']]);
        $elementoRevertido = TratamientoElemento::create(['tratamiento_sesion_id' => $sesion->id, 'tipo' => 'METAS', 'orden' => 2, 'contenido' => ['type' => 'METAS']]);
        $entrenamientoAnterior = $this->crearEntrenamiento($paciente, $version);
        $entrenamientoActual = $this->crearEntrenamiento($paciente, $version);
        $entrenamientoAnterior->update(['activo' => false]);
        $entrenamientoActual->created_at = now()->addSecond();
        $entrenamientoActual->save();

        ProgresoElemento::create([
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamientoAnterior->id,
            'tratamiento_elemento_id' => $elementoAnterior->id,
            'completado' => true,
            'fecha_estado' => now(),
            'fecha_sincronizacion' => now(),
        ]);
        ProgresoElemento::create([
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamientoActual->id,
            'tratamiento_elemento_id' => $elementoCompletado->id,
            'completado' => true,
            'fecha_estado' => now(),
            'fecha_sincronizacion' => now(),
        ]);
        ProgresoElemento::create([
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamientoActual->id,
            'tratamiento_elemento_id' => $elementoRevertido->id,
            'completado' => false,
            'fecha_estado' => now(),
            'fecha_sincronizacion' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertOk()
            ->assertExactJson([
                'elementos_completados' => [$elementoCompletado->id],
            ]);

        ProgresoElemento::query()
            ->where('entrenamiento_id', $entrenamientoActual->id)
            ->where('tratamiento_elemento_id', $elementoCompletado->id)
            ->update(['completado' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertExactJson(['elementos_completados' => []]);

        ProgresoElemento::query()
            ->where('entrenamiento_id', $entrenamientoActual->id)
            ->where('tratamiento_elemento_id', $elementoRevertido->id)
            ->update(['completado' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertExactJson(['elementos_completados' => [$elementoRevertido->id]]);
    }

    /** Verifica que un paciente sin entrenamiento activo reciba un avance vacío. */
    public function test_paciente_sin_entrenamiento_actual_recibe_elementos_completados_vacio(): void
    {
        [$user] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertOk()
            ->assertExactJson(['elementos_completados' => []]);
    }

    public function test_paciente_sin_entrenamiento_actual_recibe_datos_funcionales_vacios(): void
    {
        [$user] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/datos-funcionales-tratamiento')
            ->assertOk()
            ->assertExactJson(['quiz' => [], 'metas' => []]);
    }

    public function test_la_ruta_legacy_de_multimedia_completados_no_existe(): void
    {
        [$user] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/multimedia-completados')
            ->assertNotFound();
    }

    /**
     * Verifica que las metas se registren con el entrenamiento activo del paciente.
     */
    public function test_paciente_puede_sincronizar_metas_con_su_entrenamiento(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('c', 64)]);
        $sesion = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $elementoMetas = TratamientoElemento::create(['tratamiento_sesion_id' => $sesion->id, 'tipo' => 'METAS', 'orden' => 0, 'contenido' => ['type' => 'METAS']]);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $fecha = '2026-08-20 10:30:00';

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', [
                'userId' => $user->id,
                'metas' => [[
                    'id_meta' => 201,
                    'tratamiento_elemento_id' => $elementoMetas->id,
                    'tipo_actividad' => 'Caminar',
                    'actividad' => '30 minutos',
                    'lugar' => 'Parque',
                    'acompaniante' => null,
                    'meta_cumplida' => true,
                    'motivo_incumplimiento' => null,
                    'fecha_registro' => $fecha,
                    'fecha_usuario' => $fecha,
                    'fecha_sincronizacion' => $fecha,
                    'fecha_edicion' => null,
                    'fecha_objetivo' => $fecha,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('metas', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $elementoMetas->id,
            'id_meta' => 201,
            'actividad' => '30 minutos',
        ]);
    }

    /**
     * Verifica que un pedido de ayuda cree una alerta operativa para el paciente autenticado.
     */
    public function test_paciente_puede_registrar_un_pedido_de_ayuda(): void
    {
        [$user, $paciente] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/pedido-ayuda')
            ->assertOk()
            ->assertJsonPath('message', 'Pedido de ayuda registrado con éxito.');

        $this->assertDatabaseHas('alertas', [
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'descripcion' => 'Pedido de ayuda',
        ]);
    }

    /**
     * Verifica que los endpoints móviles no puedan utilizarse con un token de otro rol.
     */
    public function test_rechaza_usuarios_que_no_son_pacientes(): void
    {
        $user = User::create([
            'nombre' => 'Usuario del panel',
            'sexo' => 'F',
            'documento' => '87654321',
            'email' => 'panel@example.test',
            'password' => 'secreto',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', ['userId' => $user->id])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/pedido-ayuda')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertForbidden();
    }

    /**
     * Crea un usuario con el rol y el perfil requeridos por la API móvil.
     *
     * @return array{User, Paciente}
     */
    private function crearPaciente(): array
    {
        $user = User::create([
            'nombre' => 'Ana Paz',
            'sexo' => 'F',
            'documento' => '12345678',
            'email' => 'ana@example.test',
            'password' => 'secreto',
        ]);
        $user->assign(User::ROL_PACIENTE);

        $paciente = Paciente::create([
            'user_id' => $user->id,
            'nombre' => 'Ana',
            'apellido' => 'Paz',
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => $user->documento,
            'telefono' => '123456789',
            'email' => $user->email,
            'direccion' => 'Calle 1',
            'fecha_enrolamiento' => '2026-01-01',
        ]);

        return [$user, $paciente];
    }

    private function crearEntrenamiento(Paciente $paciente, ?TratamientoVersion $version = null): Entrenamiento
    {
        return Entrenamiento::create(array_merge(
            [
                'paciente_id' => $paciente->id,
                'tratamiento_version_id' => $version?->id,
                'fecha' => '2026-08-01',
                'activo' => true,
            ],
            array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-01'),
        ));
    }
}
