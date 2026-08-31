<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\CentroSalud;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use App\Services\IndicadorService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_indicadores_generales_respetan_el_alcance_de_cada_rol(): void
    {
        $this->seed(RolesSeeder::class);
        $argentina = $this->crearPais('Argentina');
        $chile = $this->crearPais('Chile');
        $centroArgentina = $this->crearCentro($argentina);
        $centroChile = $this->crearCentro($chile);
        $supervisor = $this->crearUsuario($argentina, $centroArgentina, User::ROL_SUPERVISOR);
        $promotorPropio = $this->crearUsuario($argentina, $centroArgentina, User::ROL_PROMOTOR, $supervisor->id);
        $promotorAjeno = $this->crearUsuario($argentina, $centroArgentina, User::ROL_PROMOTOR);
        $promotorChile = $this->crearUsuario($chile, $centroChile, User::ROL_PROMOTOR);
        $supervisorPais = $this->crearUsuario($argentina, $centroArgentina, User::ROL_SUPERVISOR_PAIS);
        $superusuario = $this->crearUsuario($argentina, $centroArgentina, User::ROL_SUPERUSUARIO);

        $pacienteEntrenado = $this->crearPaciente($centroArgentina, $promotorPropio, true);
        $pacienteInactivo = $this->crearPaciente($centroArgentina, $promotorPropio, false);
        $pacienteAjeno = $this->crearPaciente($centroArgentina, $promotorAjeno, true);
        $pacienteChile = $this->crearPaciente($centroChile, $promotorChile, true);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true);
        $this->crearInteraccion($pacienteAjeno, $promotorAjeno, true);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::SEGUIMIENTO_MENSUAL);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::SEGUIMIENTO_MENSUAL);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::SEGUIMIENTO_MENSUAL);
        $this->crearInteraccion($pacienteInactivo, $promotorPropio, false);
        $alerta = Alerta::create([
            'paciente_id' => $pacienteChile->id, 'tipo' => 'operativa', 'descripcion' => 'Alerta de prueba',
            'fecha' => '2026-01-01', 'user_id' => $promotorChile->id,
        ]);
        $alerta->created_at = '2026-01-01 08:00:00';
        $alerta->save();
        $this->crearInteraccion($pacienteChile, $promotorChile, true, MotivoInteraccion::CONTACTO_INICIADO_POR_PARTICIPANTE, $alerta);
        $alertaClinicaPropia = Alerta::create([
            'paciente_id' => $pacienteEntrenado->id, 'tipo' => 'clinica', 'descripcion' => 'Alerta clínica propia',
            'fecha' => '2026-01-01', 'user_id' => $promotorPropio->id,
        ]);
        $alertaClinicaAjena = Alerta::create([
            'paciente_id' => $pacienteAjeno->id, 'tipo' => 'clinica', 'descripcion' => 'Alerta clínica ajena',
            'fecha' => '2026-01-01', 'user_id' => $promotorAjeno->id,
        ]);
        $alertaClinicaPropia->created_at = '2026-01-01 08:00:00';
        $alertaClinicaPropia->save();
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::ALERTA_CLINICA, $alertaClinicaPropia);
        $this->crearInteraccion($pacienteAjeno, $promotorAjeno, false, MotivoInteraccion::ALERTA_CLINICA, $alertaClinicaAjena);
        $this->crearInteraccion($pacienteEntrenado, $promotorPropio, true, MotivoInteraccion::SEGUIMIENTO_MENSUAL);

        $service = app(IndicadorService::class);

        $this->assertSame([
            'total_participantes' => 2,
            'total_participantes_activos' => 1,
            'porcentaje_participantes_activos' => 50.0,
        ], $service->getIndicadoresGenerales($supervisor));
        $this->assertSame([
            'total_participantes' => 3,
            'total_participantes_activos' => 2,
            'porcentaje_participantes_activos' => 66.67,
        ], $service->getIndicadoresGenerales($supervisorPais));
        $this->assertSame([
            'total_participantes' => 4,
            'total_participantes_activos' => 3,
            'porcentaje_participantes_activos' => 75.0,
        ], $service->getIndicadoresGenerales($superusuario));

        $this->assertSame(['participantes_entrenados' => 1], $service->getIndicadoresEntrenamiento($supervisor));
        $this->assertSame(['participantes_entrenados' => 2], $service->getIndicadoresEntrenamiento($supervisorPais));
        $this->assertSame(['participantes_entrenados' => 2], $service->getIndicadoresEntrenamiento($superusuario));

        $this->assertSame([
            'porcentaje_seguimiento_15_dias_realizado' => 50.0,
            'porcentaje_seguimiento_mensual_realizado' => 50.0,
            'porcentaje_seguimiento_2_meses_realizado' => 50.0,
            'porcentaje_seguimiento_3_meses_realizado' => 50.0,
            'porcentaje_seguimiento_4_meses_realizado' => 50.0,
            'porcentaje_contacto_exitoso_via_llamado_telefonico' => 50.0,
        ], $service->getIndicadoresInteraccionesProgramadas($supervisor));
        $this->assertSame([
            'porcentaje_seguimiento_15_dias_realizado' => 33.33,
            'porcentaje_seguimiento_mensual_realizado' => 33.33,
            'porcentaje_seguimiento_2_meses_realizado' => 33.33,
            'porcentaje_seguimiento_3_meses_realizado' => 33.33,
            'porcentaje_seguimiento_4_meses_realizado' => 33.33,
            'porcentaje_contacto_exitoso_via_llamado_telefonico' => 66.67,
        ], $service->getIndicadoresInteraccionesProgramadas($supervisorPais));
        $this->assertSame([
            'porcentaje_seguimiento_15_dias_realizado' => 25.0,
            'porcentaje_seguimiento_mensual_realizado' => 25.0,
            'porcentaje_seguimiento_2_meses_realizado' => 25.0,
            'porcentaje_seguimiento_3_meses_realizado' => 25.0,
            'porcentaje_seguimiento_4_meses_realizado' => 25.0,
            'porcentaje_contacto_exitoso_via_llamado_telefonico' => 66.67,
        ], $service->getIndicadoresInteraccionesProgramadas($superusuario));

        $this->assertSame([
            'interacciones_alertas_clinicas_contactadas' => 1,
            'porcentaje_alertas_clinicas_contactadas' => 100.0,
            'interacciones_alertas_clinicas_contactadas_48_horas' => 1,
            'porcentaje_alertas_clinicas_contactadas_48_horas' => 100.0,
        ], $service->getIndicadoresAlertasClinicas($supervisor));
        $this->assertSame([
            'interacciones_alertas_clinicas_contactadas' => 1,
            'porcentaje_alertas_clinicas_contactadas' => 50.0,
            'interacciones_alertas_clinicas_contactadas_48_horas' => 1,
            'porcentaje_alertas_clinicas_contactadas_48_horas' => 50.0,
        ], $service->getIndicadoresAlertasClinicas($supervisorPais));
        $this->assertSame([
            'interacciones_alertas_clinicas_contactadas' => 1,
            'porcentaje_alertas_clinicas_contactadas' => 50.0,
            'interacciones_alertas_clinicas_contactadas_48_horas' => 1,
            'porcentaje_alertas_clinicas_contactadas_48_horas' => 50.0,
        ], $service->getIndicadoresAlertasClinicas($superusuario));

        $this->assertSame([
            'interacciones_alertas_operativas_contactadas' => 0,
            'porcentaje_alertas_operativas_contactadas' => 0,
            'interacciones_alertas_operativas_contactadas_72_horas' => 0,
            'porcentaje_alertas_operativas_contactadas_72_horas' => 0,
        ], $service->getIndicadoresAlertasOperativas($supervisor));
        $this->assertSame([
            'interacciones_alertas_operativas_contactadas' => 0,
            'porcentaje_alertas_operativas_contactadas' => 0,
            'interacciones_alertas_operativas_contactadas_72_horas' => 0,
            'porcentaje_alertas_operativas_contactadas_72_horas' => 0,
        ], $service->getIndicadoresAlertasOperativas($supervisorPais));
        $this->assertSame([
            'interacciones_alertas_operativas_contactadas' => 1,
            'porcentaje_alertas_operativas_contactadas' => 100.0,
            'interacciones_alertas_operativas_contactadas_72_horas' => 1,
            'porcentaje_alertas_operativas_contactadas_72_horas' => 100.0,
        ], $service->getIndicadoresAlertasOperativas($superusuario));
    }

    private function crearPais(string $nombre): Pais
    {
        return Pais::create(['nombre' => $nombre, 'codigo' => strtoupper(substr($nombre, 0, 2)), 'activo' => true]);
    }

    private function crearCentro(Pais $pais): CentroSalud
    {
        return CentroSalud::create(['nombre' => "Centro {$pais->nombre}", 'pais_id' => $pais->id, 'activo' => true]);
    }

    private function crearUsuario(Pais $pais, CentroSalud $centro, string $rol, ?int $supervisorId = null): User
    {
        $usuario = User::create([
            'nombre' => fake()->name(), 'sexo' => 'F', 'documento' => fake()->unique()->numerify('DOC-####'),
            'email' => fake()->unique()->safeEmail(), 'password' => 'password', 'pais_id' => $pais->id,
            'centro_salud_id' => $centro->id, 'supervisor_id' => $supervisorId,
        ]);
        $usuario->assign($rol);

        return $usuario;
    }

    private function crearPaciente(CentroSalud $centro, User $promotor, bool $activo): Paciente
    {
        return Paciente::create([
            'nombre' => fake()->firstName(), 'apellido' => fake()->lastName(), 'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F', 'documento' => fake()->unique()->numerify('PAC-####'), 'telefono' => '123456',
            'email' => fake()->unique()->safeEmail(), 'direccion' => 'Calle 1', 'fecha_enrolamiento' => '2026-01-01',
            'centro_salud_id' => $centro->id, 'promotor_id' => $promotor->id, 'activo' => $activo,
        ]);
    }

    private function crearInteraccion(
        Paciente $paciente,
        User $usuario,
        bool $contactado,
        MotivoInteraccion $motivo = MotivoInteraccion::CITA_FINAL,
        ?Alerta $alerta = null,
    ): Interaccion
    {
        return Interaccion::create([
            'paciente_id' => $paciente->id, 'user_id' => $usuario->id, 'contactado' => $contactado,
            'fecha' => '2026-01-01', 'hora' => '09:00', 'modalidad' => 'Llamada por teléfono / WhatsApp',
            'motivo' => $motivo, 'alerta_id' => $alerta?->id,
        ]);
    }
}
