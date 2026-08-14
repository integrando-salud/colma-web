<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PacientePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_restringe_el_paciente_al_alcance_del_promotor(): void
    {
        $pais = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $centro = CentroSalud::create(['nombre' => 'Centro', 'pais_id' => $pais->id, 'activo' => true]);
        $promotor = $this->crearUsuario($pais, $centro, 'promotor');
        $otroPromotor = $this->crearUsuario($pais, $centro, 'otro-promotor');
        $propio = $this->crearPaciente($centro, $promotor, 'propio');
        $ajeno = $this->crearPaciente($centro, $otroPromotor, 'ajeno');

        $this->assertTrue($promotor->can('view', $propio));
        $this->assertFalse($promotor->can('view', $ajeno));
        $this->assertFalse($promotor->can('changeStatus', $propio));
    }

    public function test_usuario_paciente_no_accede_al_modulo(): void
    {
        $pais = Pais::create(['nombre' => 'Chile', 'codigo' => 'CL', 'activo' => true]);
        $centro = CentroSalud::create(['nombre' => 'Centro Chile', 'pais_id' => $pais->id, 'activo' => true]);
        $usuario = $this->crearUsuario($pais, $centro, 'paciente');
        $usuario->retract(User::ROL_PROMOTOR);
        $usuario->assign(User::ROL_PACIENTE);

        $this->assertFalse($usuario->can('viewAny', Paciente::class));
        $this->assertFalse($usuario->can('create', Paciente::class));
    }

    private function crearUsuario(Pais $pais, CentroSalud $centro, string $sufijo): User
    {
        $usuario = User::create([
            'nombre' => "Promotor $sufijo",
            'sexo' => 'F',
            'documento' => "USR-$sufijo",
            'email' => "$sufijo@example.test",
            'password' => 'secret',
            'pais_id' => $pais->id,
            'centro_salud_id' => $centro->id,
        ]);
        $usuario->assign(User::ROL_PROMOTOR);

        return $usuario;
    }

    private function crearPaciente(CentroSalud $centro, User $promotor, string $sufijo): Paciente
    {
        return Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => $sufijo,
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => "PAC-$sufijo",
            'telefono' => '123456',
            'email' => "$sufijo-paciente@example.test",
            'direccion' => 'Calle 1',
            'centro_salud_id' => $centro->id,
            'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => '2026-01-01',
            'activo' => true,
        ]);
    }
}
