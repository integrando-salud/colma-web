<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Diagnostico;
use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\TratamientoElemento;
use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTratamientoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que un token de un usuario del panel no pueda consultar tratamientos.
     */
    public function test_rechaza_usuarios_que_no_son_pacientes(): void
    {
        $user = User::create([
            'nombre' => 'Usuario del panel',
            'sexo' => 'F',
            'documento' => '87654321',
            'email' => 'panel-tratamiento@example.test',
            'password' => 'secreto',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tratamiento')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->get('/api/multimedia/tratamientos/AR/DBT/semana-1/sesion-1/video.mp4')
            ->assertForbidden();
    }

    public function test_entrega_elementos_persistidos_ordenados_con_sus_recursos_y_contenido_logico(): void
    {
        config()->set('filesystems.multimedia.tratamientos_dirname', 'tratamientos-no-leer-en-api');
        [$user, $paciente] = $this->crearPacienteDiabetes();
        $version = $this->crearVersion('version A');
        $sesion = $version->sesiones()->create([
            'semana' => 1, 'sesion' => 1, 'contenido' => ['id' => 1, 'title' => 'Sesión persistida', 'duration' => '05:00'],
        ]);

        $descriptionSinRecursos = $this->crearElemento($sesion, 2, ['type' => 'DESCRIPTION', 'title' => 'Sin recursos', 'description' => 'Texto']);
        $descriptionAudio = $this->crearElemento($sesion, 0, ['type' => 'DESCRIPTION', 'title' => 'Audio', 'audio' => 'ruta-anterior']);
        $descriptionImagen = $this->crearElemento($sesion, 1, ['type' => 'DESCRIPTION', 'title' => 'Imagen', 'image' => 'ruta-anterior']);
        $descriptionAmbos = $this->crearElemento($sesion, 3, ['type' => 'DESCRIPTION', 'title' => 'Ambos', 'audio' => 'ruta-anterior', 'image' => 'ruta-anterior']);
        $video = $this->crearElemento($sesion, 4, ['type' => 'VIDEO', 'title' => 'Video', 'video' => 'ruta-anterior']);
        $quiz = $this->crearElemento($sesion, 5, ['type' => 'QUIZ', 'title' => 'Quiz', 'questions' => [['questionText' => '¿Pregunta?', 'options' => ['A', 'B'], 'correctAnswerIndex' => 1]]]);
        $metas = $this->crearElemento($sesion, 6, ['type' => 'METAS', 'title' => 'Metas', 'metas' => [['actividad' => 'Caminar']]]);
        $this->crearRecurso($sesion, $descriptionAudio, 'AUDIO', 'AR/DBT/audio.m4a');
        $this->crearRecurso($sesion, $descriptionImagen, 'IMAGE', 'AR/DBT/imagen.png');
        $this->crearRecurso($sesion, $descriptionAmbos, 'AUDIO', 'AR/DBT/ambos.m4a');
        $this->crearRecurso($sesion, $descriptionAmbos, 'IMAGE', 'AR/DBT/ambos.png');
        $recursoVideo = $this->crearRecurso($sesion, $video, 'VIDEO', 'AR/DBT/video.mp4');
        $this->crearEntrenamiento($paciente, $version);

        $contenido = $this->actingAs($user, 'sanctum')->getJson('/api/tratamiento')
            ->assertOk()
            ->json('tratamiento.semanas.0.sesiones.0.content');

        $this->assertNull($this->getJson('/api/tratamiento')->json('tratamiento.semanas.0.subSessions'));
        $this->assertNull($this->getJson('/api/tratamiento')->json('tratamiento.semanas.0.sesiones.0.multimedia'));
        $this->assertSame(['Audio', 'Imagen', 'Sin recursos', 'Ambos', 'Video', 'Quiz', 'Metas'], array_column($contenido, 'title'));
        $this->assertSame($descriptionAudio->id, $contenido[0]['tratamiento_elemento_id']);
        $this->assertStringEndsWith('/api/multimedia/tratamientos-no-leer-en-api/AR/DBT/audio.m4a', $contenido[0]['audio']);
        $this->assertSame($descriptionImagen->id, $contenido[1]['tratamiento_elemento_id']);
        $this->assertStringEndsWith('/api/multimedia/tratamientos-no-leer-en-api/AR/DBT/imagen.png', $contenido[1]['image']);
        $this->assertSame($descriptionSinRecursos->id, $contenido[2]['tratamiento_elemento_id']);
        $this->assertArrayNotHasKey('audio', $contenido[2]);
        $this->assertArrayNotHasKey('image', $contenido[2]);
        $this->assertStringEndsWith('/api/multimedia/tratamientos-no-leer-en-api/AR/DBT/ambos.m4a', $contenido[3]['audio']);
        $this->assertStringEndsWith('/api/multimedia/tratamientos-no-leer-en-api/AR/DBT/ambos.png', $contenido[3]['image']);
        $this->assertSame($video->id, $contenido[4]['tratamiento_elemento_id']);
        $this->assertArrayNotHasKey('tratamiento_multimedia_id', $contenido[4]);
        $this->assertSame($contenido[4]['video'], $contenido[4]['link']);
        $this->assertSame($quiz->id, $contenido[5]['tratamiento_elemento_id']);
        $this->assertSame('¿Pregunta?', $contenido[5]['questions'][0]['questionText']);
        $this->assertArrayNotHasKey('tratamiento_multimedia_id', $contenido[5]);
        $this->assertSame($metas->id, $contenido[6]['tratamiento_elemento_id']);
        $this->assertSame('Caminar', $contenido[6]['metas'][0]['actividad']);
        $this->assertArrayNotHasKey('tratamiento_multimedia_id', $contenido[6]);
    }

    public function test_utiliza_la_version_asociada_al_entrenamiento_y_no_la_ultima(): void
    {
        [$user, $paciente] = $this->crearPacienteDiabetes();
        $versionA = $this->crearVersion('A');
        $sesionA = $versionA->sesiones()->create(['semana' => 1, 'sesion' => 1, 'contenido' => ['id' => 1]]);
        $elementoA = $this->crearElemento($sesionA, 0, ['type' => 'QUIZ', 'title' => 'Versión A', 'questions' => []]);
        $versionB = $this->crearVersion('B');
        $sesionB = $versionB->sesiones()->create(['semana' => 1, 'sesion' => 1, 'contenido' => ['id' => 1]]);
        $elementoB = $this->crearElemento($sesionB, 0, ['type' => 'QUIZ', 'title' => 'Versión B', 'questions' => []]);
        $entrenamientoA = $this->crearEntrenamiento($paciente, $versionA);

        $this->actingAs($user, 'sanctum')->getJson('/api/tratamiento')
            ->assertJsonPath('tratamiento.semanas.0.sesiones.0.content.0.title', 'Versión A')
            ->assertJsonPath('tratamiento.semanas.0.sesiones.0.content.0.tratamiento_elemento_id', $elementoA->id);

        $entrenamientoA->update(['activo' => false]);
        $this->crearEntrenamiento($paciente, $versionB);
        $this->actingAs($user, 'sanctum')->getJson('/api/tratamiento')
            ->assertJsonPath('tratamiento.semanas.0.sesiones.0.content.0.title', 'Versión B')
            ->assertJsonPath('tratamiento.semanas.0.sesiones.0.content.0.tratamiento_elemento_id', $elementoB->id);
    }

    public function test_paciente_sin_entrenamiento_activo_recibe_objeto_vacio_aunque_exista_un_catalogo_compatible(): void
    {
        [$user] = $this->crearPacienteDiabetes();
        $this->crearVersion('catálogo que no debe usarse como fallback');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tratamiento')
            ->assertOk()
            ->assertContent('{}');
    }

    private function crearVersion(string $hash): TratamientoVersion
    {
        return TratamientoVersion::create([
            'pais' => 'AR', 'tipo' => 'DIABETES', 'hash' => str_pad($hash, 64, 'x'),
            'contenido' => ['sessions' => [['id' => 77, 'title' => 'Semana persistida', 'duration' => '10 min']]],
        ]);
    }

    /** @param array<string, mixed> $contenido */
    private function crearElemento(TratamientoSesion $sesion, int $orden, array $contenido): TratamientoElemento
    {
        return $sesion->elementos()->create(['tipo' => $contenido['type'], 'orden' => $orden, 'contenido' => $contenido]);
    }

    private function crearRecurso(TratamientoSesion $sesion, TratamientoElemento $elemento, string $tipo, string $ruta): TratamientoMultimedia
    {
        return TratamientoMultimedia::create([
            'tratamiento_elemento_id' => $elemento->id,
            'tipo' => $tipo, 'ruta' => $ruta, 'checksum' => str_repeat('a', 64),
        ]);
    }

    /** @return array{User, Paciente} */
    private function crearPacienteDiabetes(): array
    {
        $pais = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR']);
        $centro = CentroSalud::create(['nombre' => 'Centro']);
        $centro->pais_id = $pais->id;
        $centro->save();
        $user = User::create(['nombre' => 'Ana Paz', 'sexo' => 'F', 'documento' => '12345678', 'email' => 'ana@example.test', 'password' => 'secreto']);
        $user->assign(User::ROL_PACIENTE);
        $paciente = Paciente::create([
            'user_id' => $user->id, 'nombre' => 'Ana', 'apellido' => 'Paz', 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F',
            'documento' => $user->documento, 'telefono' => '123456789', 'email' => $user->email, 'direccion' => 'Calle 1',
            'centro_salud_id' => $centro->id, 'fecha_enrolamiento' => '2026-01-01',
        ]);
        Diagnostico::create(['id' => Diagnostico::DIABETES, 'nombre' => 'Diabetes', 'activo' => true]);
        $paciente->diagnosticos()->attach(Diagnostico::DIABETES);

        return [$user, $paciente];
    }

    private function crearEntrenamiento(Paciente $paciente, TratamientoVersion $version): Entrenamiento
    {
        return Entrenamiento::create(array_merge([
            'paciente_id' => $paciente->id, 'tratamiento_version_id' => $version->id, 'fecha' => '2026-08-01', 'activo' => true,
        ], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-01')));
    }
}
