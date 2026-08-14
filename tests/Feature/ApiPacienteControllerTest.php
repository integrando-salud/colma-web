<?php

namespace Tests\Feature;

use App\Models\Paciente;
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
}
