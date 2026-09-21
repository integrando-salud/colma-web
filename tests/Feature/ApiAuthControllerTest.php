<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_autentica_a_un_paciente_y_registra_su_primer_acceso(): void
    {
        [$user, $paciente] = $this->crearPaciente('12345678', 'secreto');

        $response = $this->postJson('/api/login', [
            'documento' => $user->documento,
            'password' => 'secreto',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('usuario.id', $user->id)
            ->assertJsonStructure(['token', 'usuario']);

        $this->assertNotNull($response->json('usuario.primer_login'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'mobile-app',
        ]);
        $this->assertNotNull($paciente->fresh()->fecha_primer_login_app);
    }

    public function test_rechaza_pacientes_sin_perfil_asociado(): void
    {
        $user = User::create([
            'nombre' => 'Paciente sin perfil',
            'sexo' => 'F',
            'documento' => '87654321',
            'email' => 'sin-perfil@example.test',
            'password' => Hash::make('secreto'),
        ]);
        $user->assign(User::ROL_PACIENTE);

        $this->postJson('/api/login', [
            'documento' => $user->documento,
            'password' => 'secreto',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Documento o contraseña incorrectos');
    }

    public function test_devuelve_el_centro_y_pais_del_perfil_del_paciente(): void
    {
        $paisPaciente = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $paisUsuario = Pais::create(['nombre' => 'Chile', 'codigo' => 'CL', 'activo' => true]);
        $centroPaciente = CentroSalud::create(['nombre' => 'Centro del paciente', 'activo' => true]);
        $centroPaciente->pais_id = $paisPaciente->id;
        $centroPaciente->save();
        $centroUsuario = CentroSalud::create(['nombre' => 'Centro histórico del usuario', 'activo' => true]);
        $centroUsuario->pais_id = $paisUsuario->id;
        $centroUsuario->save();
        [$user, $paciente] = $this->crearPaciente('12345679', 'secreto');
        $user->update(['pais_id' => $paisUsuario->id, 'centro_salud_id' => $centroUsuario->id]);
        $paciente->update(['centro_salud_id' => $centroPaciente->id]);

        $this->postJson('/api/login', [
            'documento' => $user->documento,
            'password' => 'secreto',
        ])
            ->assertOk()
            ->assertJsonPath('usuario.pais.id', $paisPaciente->id)
            ->assertJsonPath('usuario.pais.nombre', $paisPaciente->nombre)
            ->assertJsonPath('usuario.centro_salud.id', $centroPaciente->id)
            ->assertJsonPath('usuario.centro_salud.nombre', $centroPaciente->nombre);
    }

    public function test_bloquea_el_sexto_intento_fallido_de_inicio_de_sesion(): void
    {
        $credentials = [
            'documento' => '12345678',
            'password' => 'credencial-incorrecta',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', $credentials)
                ->assertUnauthorized();
        }

        $response = $this->postJson('/api/login', $credentials)
            ->assertTooManyRequests();

        $this->assertStringContainsString(
            'Demasiados intentos de inicio de sesión.',
            $response->json('message'),
        );
    }

    /**
     * @return array{User, Paciente}
     */
    private function crearPaciente(string $documento, string $password): array
    {
        $user = User::create([
            'nombre' => 'Ana Paz',
            'sexo' => 'F',
            'documento' => $documento,
            'email' => "$documento@example.test",
            'password' => Hash::make($password),
        ]);
        $user->assign(User::ROL_PACIENTE);

        $paciente = Paciente::create([
            'user_id' => $user->id,
            'nombre' => 'Ana',
            'apellido' => 'Paz',
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => $documento,
            'telefono' => '123456789',
            'email' => "$documento@example.test",
            'direccion' => 'Calle 1',
            'fecha_enrolamiento' => '2026-01-01',
        ]);

        return [$user, $paciente];
    }
}
