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

class ProgresoElementoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sincroniza_description_video_quiz_y_metas_con_sus_datos_derivables(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = $this->crearVersion('DIABETES', 'a');
        $elementos = $this->crearElementos($version, ['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS']);
        $entrenamiento = $this->crearEntrenamiento($paciente, $version);
        $progresos = collect($elementos)->values()->map(fn ($elemento, $indice) => $this->payload($elemento->id, true, "2026-08-20 10:3$indice:00"))->all();
        $progresos[2]['treatment_id'] = 'metadata-falsa';
        $progresos[2]['week_id'] = 999;
        $progresos[2]['session_id'] = 999;
        $progresos[2]['multimedia_type'] = 'metadata-falsa';

        $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
            'userId' => $user->id,
            'progreso_elementos' => $progresos,
        ])->assertOk();

        $this->assertDatabaseCount('progreso_elementos', 4);
        foreach ($elementos as $elemento) {
            $this->assertDatabaseHas('progreso_elementos', [
                'paciente_id' => $paciente->id, 'entrenamiento_id' => $entrenamiento->id,
                'tratamiento_elemento_id' => $elemento->id, 'completado' => 1,
            ]);
        }
        $progreso = ProgresoElemento::with('tratamientoElemento.sesion.version')->where('tratamiento_elemento_id', $elementos[2]->id)->sole();
        $this->assertSame('QUIZ', $progreso->tratamientoElemento->tipo);
        $this->assertSame(1, $progreso->tratamientoElemento->sesion->semana);
        $this->assertSame(1, $progreso->tratamientoElemento->sesion->sesion);
        $this->assertSame('DIABETES', $progreso->tratamientoElemento->sesion->version->tipo);
    }

    public function test_es_idempotente_para_reintentos_y_lotes_duplicados_y_conserva_la_fecha_del_dispositivo(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = $this->crearVersion('DIABETES', 'b');
        $elemento = $this->crearElementos($version, ['VIDEO'])[0];
        $this->crearEntrenamiento($paciente, $version);
        $evento = $this->payload($elemento->id, true, '2026-08-20 10:30:00');

        foreach (range(1, 2) as $_) {
            $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
                'userId' => $user->id, 'progreso_elementos' => [$evento, $evento],
            ])->assertOk();
        }

        $this->assertDatabaseCount('progreso_elementos', 1);
        $this->assertDatabaseHas('progreso_elementos', [
            'tratamiento_elemento_id' => $elemento->id, 'fecha_estado' => '2026-08-20 13:30:00',
        ]);
    }

    public function test_metas_admite_reversion_generica_y_descarta_eventos_fuera_de_orden(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $version = $this->crearVersion('DIABETES', 'c');
        $meta = $this->crearElementos($version, ['METAS'])[0];
        $this->crearEntrenamiento($paciente, $version);

        $this->sincronizar($user, $this->payload($meta->id, true, '2026-08-20 10:30:00'));
        $this->sincronizar($user, $this->payload($meta->id, false, '2026-08-21 10:30:00'));
        $this->sincronizar($user, $this->payload($meta->id, true, '2026-08-19 10:30:00'));

        $this->assertDatabaseHas('progreso_elementos', [
            'tratamiento_elemento_id' => $meta->id, 'completado' => 0, 'fecha_estado' => '2026-08-21 13:30:00',
        ]);
    }

    public function test_rechaza_elementos_inexistentes_o_de_otra_version_o_tratamiento(): void
    {
        [$user, $paciente] = $this->crearPaciente();
        $versionActiva = $this->crearVersion('DIABETES', 'd');
        $this->crearEntrenamiento($paciente, $versionActiva);
        $otraVersion = $this->crearVersion('DIABETES', 'e');
        $otroTratamiento = $this->crearVersion('HTA', 'f');
        $elementoOtraVersion = $this->crearElementos($otraVersion, ['QUIZ'])[0];
        $elementoOtroTratamiento = $this->crearElementos($otroTratamiento, ['METAS'])[0];

        foreach ([999999, $elementoOtraVersion->id, $elementoOtroTratamiento->id] as $elementoId) {
            $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
                'userId' => $user->id,
                'progreso_elementos' => [$this->payload($elementoId, true, '2026-08-20 10:30:00')],
            ])->assertStatus(422);
        }
        $this->assertDatabaseCount('progreso_elementos', 0);
    }

    public function test_aisla_pacientes_y_entrenamientos(): void
    {
        [$userUno, $pacienteUno] = $this->crearPaciente();
        [$userDos, $pacienteDos] = $this->crearPaciente();
        $versionA = $this->crearVersion('DIABETES', 'g');
        $versionB = $this->crearVersion('DIABETES', 'h');
        $elementoA = $this->crearElementos($versionA, ['DESCRIPTION'])[0];
        $elementoB = $this->crearElementos($versionB, ['QUIZ'])[0];
        $entrenamientoUnoA = $this->crearEntrenamiento($pacienteUno, $versionA);
        $entrenamientoDos = $this->crearEntrenamiento($pacienteDos, $versionA);

        $this->sincronizar($userUno, $this->payload($elementoA->id, true, '2026-08-20 10:30:00'));
        $this->sincronizar($userDos, $this->payload($elementoA->id, true, '2026-08-20 10:30:00'));
        $entrenamientoUnoA->update(['activo' => false]);
        $entrenamientoUnoB = $this->crearEntrenamiento($pacienteUno, $versionB);
        $this->sincronizar($userUno, $this->payload($elementoB->id, true, '2026-08-21 10:30:00'));

        $this->assertDatabaseCount('progreso_elementos', 3);
        $this->assertDatabaseHas('progreso_elementos', ['paciente_id' => $pacienteUno->id, 'entrenamiento_id' => $entrenamientoUnoA->id, 'tratamiento_elemento_id' => $elementoA->id]);
        $this->assertDatabaseHas('progreso_elementos', ['paciente_id' => $pacienteDos->id, 'entrenamiento_id' => $entrenamientoDos->id, 'tratamiento_elemento_id' => $elementoA->id]);
        $this->assertDatabaseHas('progreso_elementos', ['paciente_id' => $pacienteUno->id, 'entrenamiento_id' => $entrenamientoUnoB->id, 'tratamiento_elemento_id' => $elementoB->id]);
    }

    private function sincronizar(User $user, array $evento): void
    {
        $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
            'userId' => $user->id, 'progreso_elementos' => [$evento],
        ])->assertOk();
    }

    /** @return array<string, int|bool|string> */
    private function payload(int $elementoId, bool $completado, string $fechaEstado): array
    {
        return [
            'tratamiento_elemento_id' => $elementoId,
            'completado' => $completado, 'fecha_estado' => $fechaEstado, 'fecha_sincronizacion' => $fechaEstado,
        ];
    }

    private function crearVersion(string $tipo, string $hash): TratamientoVersion
    {
        return TratamientoVersion::create(['pais' => 'AR', 'tipo' => $tipo, 'hash' => str_pad($hash, 64, $hash)]);
    }

    /** @param array<int, string> $tipos @return array<int, TratamientoElemento> */
    private function crearElementos(TratamientoVersion $version, array $tipos): array
    {
        $sesion = $version->sesiones()->create(['semana' => 1, 'sesion' => 1]);
        return array_map(fn (string $tipo, int $orden) => $sesion->elementos()->create(['tipo' => $tipo, 'orden' => $orden, 'contenido' => ['type' => $tipo]]), $tipos, array_keys($tipos));
    }

    /** @return array{User, Paciente} */
    private function crearPaciente(): array
    {
        static $contador = 0;
        $contador++;
        $user = User::create(['nombre' => "Paciente $contador", 'sexo' => 'F', 'documento' => "USR-$contador", 'email' => "paciente-$contador@example.test", 'password' => 'secreto']);
        $user->assign(User::ROL_PACIENTE);
        $paciente = Paciente::create([
            'user_id' => $user->id, 'nombre' => 'Paciente', 'apellido' => (string) $contador, 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F',
            'documento' => "PAC-$contador", 'telefono' => '123456789', 'email' => $user->email, 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '2026-01-01',
        ]);
        return [$user, $paciente];
    }

    private function crearEntrenamiento(Paciente $paciente, TratamientoVersion $version): Entrenamiento
    {
        return Entrenamiento::create(array_merge([
            'paciente_id' => $paciente->id, 'tratamiento_version_id' => $version->id, 'fecha' => '2026-08-01', 'activo' => true,
        ], array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), '2026-08-01')));
    }
}
