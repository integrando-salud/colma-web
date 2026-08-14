<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Pais;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }

    public function test_limita_la_administracion_al_alcance_del_rol(): void
    {
        $argentina = $this->crearPais('Argentina');
        $chile = $this->crearPais('Chile');
        $supervisor = $this->crearUsuario($argentina, User::ROL_SUPERVISOR);
        $promotorPropio = $this->crearUsuario($argentina, User::ROL_PROMOTOR, $supervisor->id);
        $promotorAjeno = $this->crearUsuario($argentina, User::ROL_PROMOTOR);
        $promotorExtranjero = $this->crearUsuario($chile, User::ROL_PROMOTOR, $supervisor->id);
        $supervisorPais = $this->crearUsuario($argentina, User::ROL_SUPERVISOR_PAIS);
        $superusuario = $this->crearUsuario($argentina, User::ROL_SUPERUSUARIO);
        $paciente = $this->crearUsuario($argentina, User::ROL_PACIENTE);

        $this->assertTrue(Gate::forUser($supervisor)->allows('update', $promotorPropio));
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $promotorAjeno));
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $promotorExtranjero));
        $this->assertFalse(Gate::forUser($supervisor)->allows('delete', $promotorPropio));

        $this->assertTrue(Gate::forUser($supervisorPais)->allows('update', $promotorAjeno));
        $this->assertFalse(Gate::forUser($supervisorPais)->allows('update', $promotorExtranjero));
        $this->assertTrue(Gate::forUser($superusuario)->allows('delete', $supervisorPais));
        $this->assertFalse(Gate::forUser($superusuario)->allows('update', $paciente));
    }

    private function crearPais(string $nombre): Pais
    {
        return Pais::create([
            'nombre' => $nombre,
            'codigo' => strtoupper(substr($nombre, 0, 2)),
            'activo' => true,
        ]);
    }

    private function crearUsuario(Pais $pais, string $rol, ?int $supervisorId = null): User
    {
        $centro = CentroSalud::create([
            'nombre' => "Centro {$pais->id}-{$rol}-" . random_int(1, 999999),
            'pais_id' => $pais->id,
            'activo' => true,
        ]);
        $usuario = User::create([
            'nombre' => "Usuario {$rol}",
            'sexo' => 'F',
            'documento' => "{$rol}-" . random_int(1, 999999),
            'email' => "{$rol}-" . uniqid() . '@example.test',
            'password' => 'secreto',
            'pais_id' => $pais->id,
            'centro_salud_id' => $centro->id,
            'supervisor_id' => $supervisorId,
        ]);
        $usuario->assign($rol);

        return $usuario;
    }
}
