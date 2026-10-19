<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\CentroSalud;
use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\ProgresoElemento;
use App\Models\TratamientoElemento;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use App\Models\User;
use App\Services\IndicadorService;
use App\Services\PacienteService;
use App\Services\UsuarioService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PacientesOcultosTest extends TestCase
{
    use RefreshDatabase;

    private CentroSalud $centro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $pais = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $this->centro = $pais->centrosSalud()->create(['nombre' => 'Centro', 'activo' => true]);
    }

    public function test_listados_incluyen_ocultos_y_contadores_los_excluyen_para_todos_los_roles(): void
    {
        $superusuario = $this->crearUsuario(User::ROL_SUPERUSUARIO);
        $supervisorPais = $this->crearUsuario(User::ROL_SUPERVISOR_PAIS);
        $supervisor = $this->crearUsuario(User::ROL_SUPERVISOR);
        $promotor = $this->crearUsuario(User::ROL_PROMOTOR, $supervisor);
        $visible = $this->crearPaciente($promotor);
        $inactivo = $this->crearPaciente($promotor, false);
        $oculto = $this->crearPaciente($promotor);
        $ocultoInactivo = $this->crearPaciente($promotor, false);
        $this->ocultar($oculto);
        $this->ocultar($ocultoInactivo);

        // Un paciente ajeno permanece fuera del alcance del supervisor y promotor.
        $otroPromotor = $this->crearUsuario(User::ROL_PROMOTOR);
        $ajeno = $this->crearPaciente($otroPromotor);
        $ajenoOculto = $this->crearPaciente($otroPromotor);
        $this->ocultar($ajenoOculto);

        $service = app(UsuarioService::class);
        foreach ([$superusuario, $supervisorPais, $supervisor, $promotor] as $usuario) {
            $this->actingAs($usuario);
            $alcanceAmplio = in_array($usuario->id, [$superusuario->id, $supervisorPais->id], true);
            $ids = [$visible->id, $inactivo->id, $oculto->id, $ocultoInactivo->id];
            if ($alcanceAmplio) {
                $ids[] = $ajeno->id;
                $ids[] = $ajenoOculto->id;
            }

            $this->assertEqualsCanonicalizing($ids, app(PacienteService::class)->queryGetPacientes()->pluck('pacientes.id')->all());
            $totalEstadisticas = $alcanceAmplio ? 3 : 2;
            $activos = $alcanceAmplio ? 2 : 1;
            $this->assertSame($totalEstadisticas, $service->countPacientes($usuario->id));
            $this->assertSame($activos, $service->countPacientesActivos($usuario->id));
            $this->assertSame($activos, $service->countPacientesConAlertas($usuario->id));
            $this->assertSame($activos, $service->countPacientesConContactosPendientes($usuario->id));
            $this->assertSame($totalEstadisticas * 2, $service->countAlertas($usuario->id));
        }

        // La grilla usa subconsultas SQL independientes de los contadores anteriores.
        $this->actingAs($superusuario);
        $filas = $service->queryGetUsuarios()->get()->keyBy('id');
        foreach ([$superusuario, $supervisorPais, $supervisor, $promotor] as $usuario) {
            $this->assertSame($service->countPacientes($usuario->id), (int) $filas[$usuario->id]->pacientes_count);
            $this->assertSame($service->countAlertas($usuario->id), (int) $filas[$usuario->id]->alertas_count);
        }

        DB::table('pacientes')->where('id', $oculto->id)->update(['oculto' => false]);
        $this->actingAs($promotor);
        $this->assertEqualsCanonicalizing([$visible->id, $inactivo->id, $oculto->id, $ocultoInactivo->id], app(PacienteService::class)->queryGetPacientes()->pluck('pacientes.id')->all());
        $this->assertSame(3, $service->countPacientes($promotor->id));
    }

    public function test_ocultos_no_modifican_ningun_grupo_de_indicadores(): void
    {
        $usuario = $this->crearUsuario(User::ROL_SUPERUSUARIO);
        $promotor = $this->crearUsuario(User::ROL_PROMOTOR);
        $visible = $this->crearPaciente($promotor);
        $this->crearProgresoCompleto($visible);
        $service = app(IndicadorService::class);
        $metodos = [
            'getIndicadoresGenerales', 'getIndicadoresEntrenamiento',
            'getIndicadoresInteraccionesProgramadas', 'getIndicadoresAlertasClinicas',
            'getIndicadoresAlertasOperativas', 'getIndicadoresApp',
        ];
        $esperados = [];
        foreach ($metodos as $metodo) {
            $esperados[$metodo] = $service->$metodo($usuario);
        }
        $this->assertSame(100.0, $esperados['getIndicadoresApp']['porcentaje_adherencia_sesiones']);

        $oculto = $this->crearPaciente($promotor);
        $oculto->interacciones()->whereNull('alerta_id')
            ->where('motivo', '!=', MotivoInteraccion::CITA_FINAL->value)
            ->update(['contactado' => false]);
        // Alerta adicional sin contactar para detectar exclusiones incompletas del denominador.
        foreach (['clinica', 'operativa'] as $tipo) {
            Alerta::create([
                'paciente_id' => $oculto->id, 'tipo' => $tipo,
                'descripcion' => 'Sin contacto', 'fecha' => '2026-01-01',
            ]);
        }
        foreach ($metodos as $metodo) {
            $this->assertNotEquals($esperados[$metodo], $service->$metodo($usuario), $metodo);
        }

        $this->ocultar($oculto);
        foreach ($metodos as $metodo) {
            $this->assertSame($esperados[$metodo], $service->$metodo($usuario), $metodo);
        }

        $this->ocultar($visible);
        $this->assertSame(0, $service->getIndicadoresGenerales($usuario)['total_participantes']);
        $this->assertEquals(0, $service->getIndicadoresApp($usuario)['porcentaje_adherencia_sesiones']);
    }

    public function test_ocultar_conserva_datos_acceso_a_la_ficha_y_acceso_movil(): void
    {
        $promotor = $this->crearUsuario(User::ROL_PROMOTOR);
        $paciente = $this->crearPaciente($promotor);
        $this->assertFalse($paciente->fresh()->oculto);
        $usuarioPaciente = $this->crearUsuario(User::ROL_PACIENTE);
        $paciente->update(['user_id' => $usuarioPaciente->id]);
        $this->ocultar($paciente);

        $this->assertTrue($paciente->fresh()->oculto);
        $this->assertTrue($promotor->can('view', $paciente));
        $this->assertTrue($promotor->can('update', $paciente));
        $this->assertSame($paciente->id, $usuarioPaciente->paciente->id);
        $this->assertSame(2, $paciente->alertas()->count());
        $this->assertSame(8, $paciente->interacciones()->count());
        $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, 'deleted_at' => null, 'activo' => true]);

        $this->withoutVite()->actingAs($promotor)
            ->get(route('dashboard.pacientes.edit', $paciente))
            ->assertOk();

        $this->actingAs($usuarioPaciente, 'sanctum')
            ->getJson('/api/elementos-completados')
            ->assertOk()
            ->assertExactJson(['elementos_completados' => []]);
    }

    private function crearUsuario(string $rol, ?User $supervisor = null): User
    {
        $usuario = User::create(User::factory()->raw([
            'pais_id' => $this->centro->pais_id,
            'centro_salud_id' => $this->centro->id,
            'supervisor_id' => $supervisor?->id,
        ]));
        $usuario->assign($rol);

        return $usuario;
    }

    private function crearPaciente(User $promotor, bool $activo = true): Paciente
    {
        $paciente = Paciente::create([
            'nombre' => 'Ana', 'apellido' => 'Paz', 'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F', 'documento' => fake()->unique()->numerify('########'),
            'telefono' => '123456', 'direccion' => 'Calle 1',
            'centro_salud_id' => $this->centro->id, 'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => '2026-01-01', 'activo' => $activo,
        ]);
        $interaccion = [
            'paciente_id' => $paciente->id, 'user_id' => $promotor->id,
            'contactado' => true, 'fecha' => '2026-01-01', 'hora' => '09:00',
            'modalidad' => Interaccion::MODALIDADES[0],
            'nueva_fecha_contacto' => '2026-12-01',
        ];
        foreach ([MotivoInteraccion::CITA_FINAL, MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS,
            ...array_fill(0, 4, MotivoInteraccion::SEGUIMIENTO_MENSUAL)] as $motivo) {
            Interaccion::create([...$interaccion, 'motivo' => $motivo]);
        }
        foreach (['clinica', 'operativa'] as $tipo) {
            $alerta = Alerta::create([
                'paciente_id' => $paciente->id, 'tipo' => $tipo,
                'descripcion' => 'Alerta de prueba', 'fecha' => '2026-01-01',
            ]);
            $alerta->created_at = '2026-01-01 08:00:00';
            $alerta->save();
            Interaccion::create([
                ...$interaccion, 'alerta_id' => $alerta->id,
                'motivo' => MotivoInteraccion::CONTACTO_INICIADO_POR_PARTICIPANTE,
            ]);
        }

        return $paciente;
    }

    private function ocultar(Paciente $paciente): void
    {
        DB::table('pacientes')->where('id', $paciente->id)->update(['oculto' => true]);
    }

    private function crearProgresoCompleto(Paciente $paciente): void
    {
        $version = TratamientoVersion::create(['pais' => 'AR', 'tipo' => 'HTA', 'hash' => str_repeat('a', 64)]);
        $sesion = TratamientoSesion::create(['tratamiento_version_id' => $version->id, 'semana' => 1, 'sesion' => 1]);
        $elemento = TratamientoElemento::create([
            'tratamiento_sesion_id' => $sesion->id, 'tipo' => 'VIDEO',
            'orden' => 0, 'contenido' => ['type' => 'VIDEO'],
        ]);
        $fecha = now()->subDays(8)->toDateString();
        $entrenamiento = Entrenamiento::create([
            'paciente_id' => $paciente->id, 'tratamiento_version_id' => $version->id,
            'fecha' => $fecha, 'activo' => true,
            ...array_fill_keys(array_keys(Entrenamiento::DIAS_SUMA), $fecha),
        ]);
        ProgresoElemento::create([
            'paciente_id' => $paciente->id, 'entrenamiento_id' => $entrenamiento->id,
            'tratamiento_elemento_id' => $elemento->id, 'completado' => true,
            'fecha_estado' => now(), 'fecha_sincronizacion' => now(),
        ]);
    }
}
