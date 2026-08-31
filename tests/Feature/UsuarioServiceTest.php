<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\CentroSalud;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use App\Services\UsuarioService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsuarioService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->service = app(UsuarioService::class);
    }

    public function test_query_get_usuarios_aplica_alcance_filtros_y_ordenamiento_seguro(): void
    {
        $pais = $this->crearPais('Argentina');
        $otroPais = $this->crearPais('Chile');
        $centro = $this->crearCentro($pais);
        $supervisor = $this->crearUsuario($pais, $centro, User::ROL_SUPERVISOR);
        $propio = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR, ['nombre' => 'Ana', 'supervisor_id' => $supervisor->id]);
        $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR, ['nombre' => 'Ana ajena']);
        $this->crearUsuario($otroPais, $this->crearCentro($otroPais), User::ROL_PROMOTOR, ['nombre' => 'Ana extranjera', 'supervisor_id' => $supervisor->id]);
        $this->actingAs($supervisor);

        $usuarios = $this->service->queryGetUsuarios([
            'nombre' => 'Ana',
            'orderBy' => 'nombre; drop table users',
            'orderDir' => 'invalid',
        ])->get();

        $this->assertSame([$propio->id], $usuarios->pluck('id')->all());
        $query = $this->service->queryGetUsuarios(['orderBy' => 'nombre; drop table users', 'orderDir' => 'invalid'])->getQuery();
        $this->assertSame('id', $query->orders[0]['column']);
        $this->assertSame('desc', $query->orders[0]['direction']);
    }

    public function test_query_get_usuarios_no_expone_promotores_ajenos_a_promotores_ni_pacientes(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentro($pais);
        $promotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $otroPromotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $paciente = $this->crearUsuario($pais, $centro, User::ROL_PACIENTE);

        $this->actingAs($promotor);
        $this->assertSame([$promotor->id], $this->service->queryGetUsuarios(['onlyPromotores' => true])->pluck('id')->all());

        $this->actingAs($paciente);
        $this->assertSame([], $this->service->queryGetUsuarios(['onlyPromotores' => true])->pluck('id')->all());
        $this->assertNotSame($promotor->id, $otroPromotor->id);
    }

    public function test_store_usuario_crea_actualiza_y_sincroniza_el_rol(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentro($pais);
        $supervisor = $this->crearUsuario($pais, $centro, User::ROL_SUPERVISOR);

        $usuario = $this->service->storeUsuario($this->datosUsuario($pais, $centro, [
            'rol' => User::ROL_PROMOTOR,
            'supervisor_id' => $supervisor->id,
            'pais_id' => 999,
        ]));

        $this->assertSame($pais->id, $usuario->pais_id);
        $this->assertTrue($usuario->isA(User::ROL_PROMOTOR));
        $this->assertTrue(Hash::check('secreto', $usuario->password));

        $actualizado = $this->service->storeUsuario($this->datosUsuario($pais, $centro, [
            'id' => $usuario->id,
            'nombre' => 'Nombre actualizado',
            'rol' => User::ROL_SUPERVISOR,
            'password' => '',
        ]));

        $this->assertSame('Nombre actualizado', $actualizado->nombre);
        $this->assertTrue($actualizado->isA(User::ROL_SUPERVISOR));
        $this->assertNull($actualizado->supervisor_id);
        $this->assertTrue(Hash::check('secreto', $actualizado->fresh()->password));
    }

    public function test_store_usuario_asigna_el_permiso_de_indicadores_a_los_roles_autorizados(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentro($pais);

        foreach ([User::ROL_SUPERUSUARIO, User::ROL_SUPERVISOR_PAIS, User::ROL_SUPERVISOR] as $rol) {
            $usuario = $this->service->storeUsuario($this->datosUsuario($pais, $centro, ['rol' => $rol]));

            $this->assertTrue($usuario->can('ver-indicadores'));
        }

        $promotor = $this->service->storeUsuario($this->datosUsuario($pais, $centro, [
            'rol' => User::ROL_PROMOTOR,
        ]));

        $this->assertFalse($promotor->can('ver-indicadores'));
    }

    public function test_delete_usuario_realiza_borrado_logico(): void
    {
        $usuario = $this->crearUsuario($this->crearPais('Argentina'), null);

        $this->assertTrue($this->service->deleteUsuario($usuario));
        $this->assertSoftDeleted('users', ['id' => $usuario->id]);
    }

    public function test_reactivate_usuario_restaura_un_borrado_logico(): void
    {
        $usuario = $this->crearUsuario($this->crearPais('Argentina'), null);
        $usuario->delete();

        $this->assertTrue($this->service->reactivateUsuario($usuario));
        $this->assertNull($usuario->fresh()->deleted_at);
    }

    public function test_update_password_la_persiste_cifrada(): void
    {
        $usuario = $this->crearUsuario($this->crearPais('Argentina'), null);

        $this->assertTrue($this->service->updatePassword($usuario, 'nueva-clave'));
        $this->assertTrue(Hash::check('nueva-clave', $usuario->fresh()->password));
    }

    public function test_count_pacientes_respeta_el_alcance_del_rol_e_ids_inexistentes(): void
    {
        [$promotor, $supervisor, $supervisorPais, $superusuario] = $this->crearEscenarioMetricas();

        $this->assertSame(2, $this->service->countPacientes($promotor->id));
        $this->assertSame(2, $this->service->countPacientes($supervisor->id));
        $this->assertSame(2, $this->service->countPacientes($supervisorPais->id));
        $this->assertSame(3, $this->service->countPacientes($superusuario->id));
        $this->assertSame(0, $this->service->countPacientes(999999));
    }

    public function test_count_pacientes_activos_respeta_el_alcance_del_rol(): void
    {
        [$promotor, $supervisor, $supervisorPais, $superusuario] = $this->crearEscenarioMetricas();

        $this->assertSame(1, $this->service->countPacientesActivos($promotor->id));
        $this->assertSame(1, $this->service->countPacientesActivos($supervisor->id));
        $this->assertSame(1, $this->service->countPacientesActivos($supervisorPais->id));
        $this->assertSame(2, $this->service->countPacientesActivos($superusuario->id));
        $this->assertSame(0, $this->service->countPacientesActivos(999999));
    }

    public function test_count_pacientes_con_alertas_cuenta_pacientes_y_no_alertas(): void
    {
        [$promotor, $supervisor, $supervisorPais, $superusuario] = $this->crearEscenarioMetricas(true);

        $this->assertSame(1, $this->service->countPacientesConAlertas($promotor->id));
        $this->assertSame(1, $this->service->countPacientesConAlertas($supervisor->id));
        $this->assertSame(1, $this->service->countPacientesConAlertas($supervisorPais->id));
        $this->assertSame(2, $this->service->countPacientesConAlertas($superusuario->id));
        $this->assertSame(0, $this->service->countPacientesConAlertas(999999));
    }

    public function test_count_alertas_cuenta_cada_alerta_pendiente_del_alcance(): void
    {
        [$promotor, $supervisor, $supervisorPais, $superusuario] = $this->crearEscenarioMetricas(true);

        $this->assertSame(3, $this->service->countAlertas($promotor->id));
        $this->assertSame(3, $this->service->countAlertas($supervisor->id));
        $this->assertSame(3, $this->service->countAlertas($supervisorPais->id));
        $this->assertSame(4, $this->service->countAlertas($superusuario->id));
        $this->assertSame(0, $this->service->countAlertas(999999));
    }

    public function test_count_pacientes_con_contactos_pendientes_detecta_alertas_y_contactos_programados(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentro($pais);
        $promotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $conAlerta = $this->crearPaciente($centro, $promotor, true);
        $programado = $this->crearPaciente($centro, $promotor, true);
        $contactado = $this->crearPaciente($centro, $promotor, true);
        $this->crearAlerta($conAlerta);
        $this->crearInteraccion($programado, $promotor, ['nueva_fecha_contacto' => '2026-08-10']);
        $this->crearInteraccion($contactado, $promotor, ['nueva_fecha_contacto' => '2026-08-10']);
        $this->crearInteraccion($contactado, $promotor, ['contactado' => true, 'fecha' => '2026-08-11']);

        $this->assertSame(2, $this->service->countPacientesConContactosPendientes($promotor->id));
        $this->assertSame(0, $this->service->countPacientesConContactosPendientes(999999));
    }

    /** @return array{User, User, User, User} */
    private function crearEscenarioMetricas(bool $crearAlertas = false): array
    {
        $pais = $this->crearPais('Argentina');
        $otroPais = $this->crearPais('Chile');
        $centro = $this->crearCentro($pais);
        $otroCentro = $this->crearCentro($otroPais);
        $supervisor = $this->crearUsuario($pais, $centro, User::ROL_SUPERVISOR);
        $promotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR, ['supervisor_id' => $supervisor->id]);
        $supervisorPais = $this->crearUsuario($pais, $centro, User::ROL_SUPERVISOR_PAIS);
        $superusuario = $this->crearUsuario($pais, $centro, User::ROL_SUPERUSUARIO);
        $activo = $this->crearPaciente($centro, $promotor, true);
        $inactivo = $this->crearPaciente($centro, $promotor, false);
        $externo = $this->crearPaciente($otroCentro, $this->crearUsuario($otroPais, $otroCentro, User::ROL_PROMOTOR), true);

        if ($crearAlertas) {
            $this->crearAlerta($activo);
            $this->crearAlerta($activo);
            $this->crearAlerta($inactivo);
            $this->crearAlerta($externo);
        }

        return [$promotor, $supervisor, $supervisorPais, $superusuario];
    }

    private function crearPais(string $nombre): Pais
    {
        return Pais::create(['nombre' => $nombre, 'codigo' => strtoupper(substr($nombre, 0, 2)), 'activo' => true]);
    }

    private function crearCentro(Pais $pais): CentroSalud
    {
        $centro = new CentroSalud(['nombre' => "Centro {$pais->id}", 'activo' => true]);
        $centro->pais_id = $pais->id;
        $centro->save();

        return $centro;
    }

    private function crearUsuario(Pais $pais, ?CentroSalud $centro, ?string $rol = null, array $atributos = []): User
    {
        static $numero = 0;
        $numero++;
        $usuario = User::create(array_merge($this->datosUsuario($pais, $centro, [
            'nombre' => "Usuario {$numero}",
            'documento' => "DOC-{$numero}",
            'email' => "usuario{$numero}@example.test",
        ]), $atributos));

        if ($rol) {
            $usuario->assign($rol);
        }

        return $usuario;
    }

    private function datosUsuario(Pais $pais, ?CentroSalud $centro, array $atributos = []): array
    {
        static $numero = 1000;
        $numero++;

        return array_merge([
            'nombre' => 'Nuevo usuario', 'sexo' => 'F', 'documento' => "NEW-{$numero}",
            'email' => "new{$numero}@example.test", 'password' => 'secreto', 'pais_id' => $pais->id,
            'centro_salud_id' => $centro?->id, 'supervisor_id' => null, 'rol' => User::ROL_PROMOTOR,
        ], $atributos);
    }

    private function crearPaciente(CentroSalud $centro, User $promotor, bool $activo): Paciente
    {
        static $numero = 0;
        $numero++;

        return Paciente::create([
            'nombre' => 'Paciente', 'apellido' => "{$numero}", 'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F', 'documento' => "PAC-{$numero}", 'telefono' => '123456',
            'email' => "paciente{$numero}@example.test", 'direccion' => 'Calle 1',
            'centro_salud_id' => $centro->id, 'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => '2026-01-01', 'activo' => $activo,
        ]);
    }

    private function crearAlerta(Paciente $paciente): Alerta
    {
        return Alerta::create(['paciente_id' => $paciente->id, 'tipo' => 'operativa', 'descripcion' => 'Pendiente', 'fecha' => '2026-08-01']);
    }

    private function crearInteraccion(Paciente $paciente, User $usuario, array $atributos = []): Interaccion
    {
        return Interaccion::create(array_merge([
            'paciente_id' => $paciente->id, 'user_id' => $usuario->id, 'contactado' => false,
            'fecha' => '2026-08-01', 'hora' => '10:00', 'modalidad' => 'Llamada por teléfono / WhatsApp',
            'motivo' => 'Seguimiento 2 semanas luego del entrenamiento',
        ], $atributos));
    }
}
