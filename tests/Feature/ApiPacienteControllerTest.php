<?php

namespace Tests\Feature;

use App\Models\Entrenamiento;
use App\Models\MultimediaRead;
use App\Models\Paciente;
use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use App\Models\User;
use App\Services\TratamientoService;
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
     * Verifica que las sesiones completadas se registren con sus datos de contexto.
     */
    public function test_paciente_puede_sincronizar_las_sesiones_multimedia_completadas(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesionUno = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $sesionDos = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 2]);
        $multimediaUno = TratamientoMultimedia::create(['tratamiento_sesion_id' => $sesionUno->id, 'ruta' => 'tratamientos/AR/HTA/semana-1/sesion-1/video.mp4', 'tipo' => 'VIDEO', 'checksum' => str_repeat('b', 64)]);
        TratamientoMultimedia::create(['tratamiento_sesion_id' => $sesionUno->id, 'ruta' => 'tratamientos/AR/HTA/semana-1/sesion-1/guia.pdf', 'tipo' => 'ARCHIVO', 'checksum' => str_repeat('d', 64)]);
        $multimediaDos = TratamientoMultimedia::create(['tratamiento_sesion_id' => $sesionDos->id, 'ruta' => 'tratamientos/AR/HTA/semana-1/sesion-2/audio.mp3', 'tipo' => 'AUDIO', 'checksum' => str_repeat('c', 64)]);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $fecha = '2026-08-20 10:30:00';

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', [
                'userId' => $user->id,
                'multimedia_read' => [
                    [
                        'id_multimedia_read' => 101,
                        'id_usuario' => $user->id,
                        'tratamiento_multimedia_id' => $multimediaUno->id,
                        'treatment_id' => 'hta',
                        'week_id' => 1,
                        'session_id' => 1,
                        'multimedia_type' => 'video',
                        'multimedia_unique_key' => 'hta-semana-1-sesion-1',
                        'fecha_registro' => $fecha,
                        'fecha_sincronizacion' => $fecha,
                    ],
                    [
                        'id_multimedia_read' => 102,
                        'id_usuario' => $user->id,
                        'tratamiento_multimedia_id' => $multimediaDos->id,
                        'treatment_id' => 'hta',
                        'week_id' => 1,
                        'session_id' => 2,
                        'multimedia_type' => 'audio',
                        'multimedia_unique_key' => 'hta-semana-1-sesion-2',
                        'fecha_registro' => $fecha,
                        'fecha_sincronizacion' => $fecha,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount('multimedia_read', 2);
        $this->assertDatabaseHas('multimedia_read', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_multimedia_id' => $multimediaUno->id,
            'id_app' => 101,
            'tratamiento' => 'HTA',
            'semana' => 1,
            'sesion' => 1,
            'tipo' => 'VIDEO',
            'key' => 'hta-semana-1-sesion-1',
        ]);
        $this->assertDatabaseHas('multimedia_read', [
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_multimedia_id' => $multimediaDos->id,
            'id_app' => 102,
            'tratamiento' => 'HTA',
            'semana' => 1,
            'sesion' => 2,
            'tipo' => 'AUDIO',
            'key' => 'hta-semana-1-sesion-2',
        ]);
        $this->assertSame(1, app(TratamientoService::class)->getCantidadSesionesCompletadas($entrenamiento));
    }

    /**
     * Verifica que la aplicación recupere solo los contenidos completados del
     * entrenamiento activo, para restaurar su avance en otro dispositivo.
     */
    public function test_paciente_puede_obtener_los_multimedia_completados_del_entrenamiento_actual(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesion = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $multimediaCompletado = TratamientoMultimedia::create(['tratamiento_sesion_id' => $sesion->id, 'ruta' => 'video.mp4', 'tipo' => 'VIDEO', 'checksum' => str_repeat('b', 64)]);
        $multimediaRepetido = TratamientoMultimedia::create(['tratamiento_sesion_id' => $sesion->id, 'ruta' => 'guia.pdf', 'tipo' => 'ARCHIVO', 'checksum' => str_repeat('c', 64)]);
        $entrenamientoAnterior = $this->crearEntrenamiento($paciente, $version);
        $entrenamientoActual = $this->crearEntrenamiento($paciente, $version);
        $entrenamientoAnterior->update(['activo' => false]);
        $entrenamientoActual->created_at = now()->addSecond();
        $entrenamientoActual->save();

        MultimediaRead::create([
            'paciente_id' => $paciente->id,
            'entrenamiento_id' => $entrenamientoAnterior->id,
            'tratamiento_multimedia_id' => $multimediaCompletado->id,
            'tratamiento' => 'HTA',
            'semana' => 1,
            'sesion' => 1,
            'tipo' => 'VIDEO',
            'key' => 'lectura-anterior',
            'fecha_registro' => now(),
            'fecha_sincronizacion' => now(),
        ]);
        foreach ([101, 102] as $idApp) {
            MultimediaRead::create([
                'id_app' => $idApp,
                'paciente_id' => $paciente->id,
                'entrenamiento_id' => $entrenamientoActual->id,
                'tratamiento_multimedia_id' => $multimediaRepetido->id,
                'tratamiento' => 'HTA',
                'semana' => 1,
                'sesion' => 1,
                'tipo' => 'ARCHIVO',
                'key' => "lectura-$idApp",
                'fecha_registro' => now(),
                'fecha_sincronizacion' => now(),
            ]);
        }

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/multimedia-completados')
            ->assertOk()
            ->assertExactJson([
                'multimedia_completados' => [$multimediaRepetido->id],
            ]);
    }

    /** Verifica que un paciente sin entrenamiento activo reciba un avance vacío. */
    public function test_paciente_sin_entrenamiento_actual_recibe_multimedia_completados_vacio(): void
    {
        [$user] = $this->crearPaciente();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/multimedia-completados')
            ->assertOk()
            ->assertExactJson(['multimedia_completados' => []]);
    }

    /**
     * Verifica que las metas se registren con el entrenamiento activo del paciente.
     */
    public function test_paciente_puede_sincronizar_metas_con_su_entrenamiento(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $entrenamiento = $this->crearEntrenamiento($paciente);
        $fecha = '2026-08-20 10:30:00';

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/sync-data-paciente', [
                'userId' => $user->id,
                'metas' => [[
                    'id_meta' => 201,
                    'id_usuario' => $user->id,
                    'semana' => 1,
                    'sesion' => 1,
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
            'id_app' => 201,
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
            ->getJson('/api/multimedia-completados')
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
