<?php

namespace Tests\Feature;

use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\ProgresoElemento;
use App\Models\TratamientoElemento;
use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use App\Http\Resources\Dashboard\EntrenamientoResource;
use App\Models\User;
use App\Services\TratamientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TratamientoProgresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_sesion_requiere_cada_tipo_logico_y_los_recursos_auxiliares_no_suman_elementos(): void
    {
        [$paciente] = $this->crearPaciente();
        $version = $this->crearVersion('a');
        $sesion = $this->crearSesion($version, ['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS']);
        $descripcion = $sesion->elementos->firstWhere('tipo', 'DESCRIPTION');
        TratamientoMultimedia::create(['tratamiento_elemento_id' => $descripcion->id, 'ruta' => 'audio.m4a', 'tipo' => 'AUDIO', 'checksum' => str_repeat('a', 64)]);
        TratamientoMultimedia::create(['tratamiento_elemento_id' => $descripcion->id, 'ruta' => 'imagen.png', 'tipo' => 'IMAGE', 'checksum' => str_repeat('b', 64)]);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $service = app(TratamientoService::class);

        $this->assertSame(0, $service->getCantidadSesionesCompletadas($entrenamiento));
        $this->assertSame(1, $service->getResumenProgresoEntrenamiento($entrenamiento)['sesiones_totales']);
        $this->assertSame(4, $sesion->elementos()->count());

        foreach ($sesion->elementos as $elemento) {
            $this->crearProgreso($paciente, $entrenamiento, $elemento, true);
        }
        $this->assertSame(1, $service->getCantidadSesionesCompletadas($entrenamiento));

        foreach (['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS'] as $tipo) {
            $elemento = $sesion->elementos->firstWhere('tipo', $tipo);
            $elemento->progresos()->sole()->update(['completado' => false]);
            $this->assertSame(0, $service->getCantidadSesionesCompletadas($entrenamiento), "Un $tipo pendiente debe dejar incompleta la sesión.");
            $elemento->progresos()->sole()->update(['completado' => true]);
        }
    }

    public function test_semana_y_entrenamiento_reflejan_completitud_y_reversiones(): void
    {
        [$paciente] = $this->crearPaciente();
        $version = $this->crearVersion('b');
        $sesionUno = $this->crearSesion($version, ['DESCRIPTION', 'VIDEO'], 1, 1);
        $sesionDos = $this->crearSesion($version, ['DESCRIPTION', 'QUIZ', 'METAS'], 1, 2);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $service = app(TratamientoService::class);

        foreach ($sesionUno->elementos as $elemento) {
            $this->crearProgreso($paciente, $entrenamiento, $elemento, true);
        }
        $this->assertSame([
            'sesiones_totales' => 2, 'sesiones_completadas' => 1, 'semanas_totales' => 1, 'semanas_completadas' => 0,
        ], $service->getResumenProgresoEntrenamiento($entrenamiento));

        foreach ($sesionDos->elementos as $elemento) {
            $this->crearProgreso($paciente, $entrenamiento, $elemento, true);
        }
        $this->assertSame([
            'sesiones_totales' => 2, 'sesiones_completadas' => 2, 'semanas_totales' => 1, 'semanas_completadas' => 1,
        ], $service->getResumenProgresoEntrenamiento($entrenamiento));

        $sesionDos->elementos->firstWhere('tipo', 'METAS')->progresos()->sole()->update(['completado' => false]);
        $this->assertSame([
            'sesiones_totales' => 2, 'sesiones_completadas' => 1, 'semanas_totales' => 1, 'semanas_completadas' => 0,
        ], $service->getResumenProgresoEntrenamiento($entrenamiento));
    }

    public function test_combinaciones_de_elementos_y_sesion_sin_elementos_no_alteran_el_calculo(): void
    {
        [$paciente] = $this->crearPaciente();
        $version = $this->crearVersion('e');
        $sesionDescripcionVideo = $this->crearSesion($version, ['DESCRIPTION', 'VIDEO'], 1, 1);
        $sesionDescripcionQuiz = $this->crearSesion($version, ['DESCRIPTION', 'QUIZ'], 1, 2);
        $sesionDescripcionMetas = $this->crearSesion($version, ['DESCRIPTION', 'METAS'], 1, 3);
        $sesionTodos = $this->crearSesion($version, ['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS'], 1, 4);
        $version->sesiones()->create(['semana' => 2, 'sesion' => 1]);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $service = app(TratamientoService::class);

        foreach ([$sesionDescripcionVideo, $sesionDescripcionQuiz, $sesionDescripcionMetas, $sesionTodos] as $sesion) {
            foreach ($sesion->elementos as $elemento) {
                $this->crearProgreso($paciente, $entrenamiento, $elemento, true);
            }
        }

        $this->assertSame([
            'sesiones_totales' => 4, 'sesiones_completadas' => 4, 'semanas_totales' => 1, 'semanas_completadas' => 1,
        ], $service->getResumenProgresoEntrenamiento($entrenamiento));
    }

    public function test_progreso_esta_aislado_por_version_paciente_y_entrenamiento(): void
    {
        [$pacienteUno] = $this->crearPaciente();
        [$pacienteDos] = $this->crearPaciente();
        $versionA = $this->crearVersion('c');
        $versionB = $this->crearVersion('d');
        $sesionA = $this->crearSesion($versionA, ['QUIZ']);
        $sesionB = $this->crearSesion($versionB, ['QUIZ']);
        $entrenamientoUnoA = $this->crearEntrenamiento($pacienteUno, $versionA);
        $entrenamientoUnoB = $this->crearEntrenamiento($pacienteUno, $versionB);
        $entrenamientoDosA = $this->crearEntrenamiento($pacienteDos, $versionA);
        $this->crearProgreso($pacienteUno, $entrenamientoUnoA, $sesionA->elementos->sole(), true);
        $this->crearProgreso($pacienteDos, $entrenamientoDosA, $sesionA->elementos->sole(), true);
        $service = app(TratamientoService::class);

        $this->assertSame(1, $service->getCantidadSesionesCompletadas($entrenamientoUnoA));
        $this->assertSame(0, $service->getCantidadSesionesCompletadas($entrenamientoUnoB));
        $this->assertSame(1, $service->getCantidadSesionesCompletadas($entrenamientoDosA));
        $this->assertNotSame($sesionA->elementos->sole()->id, $sesionB->elementos->sole()->id);
    }

    public function test_recurso_del_dashboard_usa_el_resumen_derivado_del_catalogo(): void
    {
        [$paciente] = $this->crearPaciente();
        $version = $this->crearVersion('f');
        $sesion = $this->crearSesion($version, ['DESCRIPTION']);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $this->crearProgreso($paciente, $entrenamiento, $sesion->elementos->sole(), true);

        $resultado = (new EntrenamientoResource($entrenamiento))->resolve();

        $this->assertSame(1, $resultado['sesiones']);
        $this->assertSame(1, $resultado['sesiones_completadas']);
    }

    private function crearVersion(string $hash): TratamientoVersion
    {
        return TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'DIABETES', 'hash' => str_pad($hash, 64, $hash)]);
    }

    /** @param array<int, string> $tipos */
    private function crearSesion(TratamientoVersion $version, array $tipos, int $semana = 1, int $numeroSesion = 1): TratamientoSesion
    {
        $sesion = $version->sesiones()->create(['semana' => $semana, 'sesion' => $numeroSesion]);
        foreach ($tipos as $orden => $tipo) {
            $sesion->elementos()->create(['tipo' => $tipo, 'orden' => $orden, 'contenido' => ['type' => $tipo]]);
        }
        return $sesion->load('elementos');
    }

    private function crearProgreso(Paciente $paciente, Entrenamiento $entrenamiento, TratamientoElemento $elemento, bool $completado): ProgresoElemento
    {
        return ProgresoElemento::create([
            'paciente_id' => $paciente->id, 'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $elemento->id, 'completado' => $completado,
            'fecha_estado' => '2026-08-20 13:30:00', 'fecha_sincronizacion' => '2026-08-20 13:35:00',
        ]);
    }

    /** @return array{Paciente, User} */
    private function crearPaciente(): array
    {
        static $contador = 0;
        $contador++;
        $user = User::create(['nombre' => "Paciente $contador", 'sexo' => 'F', 'documento' => "USR-$contador", 'email' => "p-$contador@example.test", 'password' => 'secreto']);
        $paciente = Paciente::create([
            'user_id' => $user->id, 'nombre' => 'Paciente', 'apellido' => (string) $contador, 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F',
            'documento' => "PAC-$contador", 'telefono' => '123456789', 'email' => $user->email, 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '2026-01-01',
        ]);
        return [$paciente, $user];
    }

    private function crearEntrenamiento(Paciente $paciente, TratamientoVersion $version): Entrenamiento
    {
        return Entrenamiento::create(array_merge([
            'paciente_id' => $paciente->id, 'tratamiento_version_id' => $version->id, 'fecha' => '2026-08-01', 'activo' => true,
        ], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-01')));
    }
}
