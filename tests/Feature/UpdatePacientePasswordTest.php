<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Diagnostico;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UpdatePacientePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $promotor;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $pais = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $centro = CentroSalud::create(['nombre' => 'Centro', 'pais_id' => $pais->id, 'activo' => true]);
        $this->promotor = User::create([
            'nombre' => 'Promotor', 'sexo' => 'F', 'documento' => '11111111',
            'password' => 'anterior', 'pais_id' => $pais->id, 'centro_salud_id' => $centro->id,
        ]);
        $this->promotor->assign(User::ROL_PROMOTOR);
        $usuario = User::create([
            'nombre' => 'Paciente', 'sexo' => 'F', 'documento' => '22222222', 'password' => 'anterior',
        ]);
        $usuario->assign(User::ROL_PACIENTE);
        $this->paciente = Paciente::create([
            'user_id' => $usuario->id, 'nombre' => 'Ana', 'apellido' => 'Paz',
            'fecha_nacimiento' => '1980-01-01', 'sexo' => 'F', 'documento' => '22222222',
            'telefono' => '123456', 'direccion' => 'Calle 1',
            'promotor_id' => $this->promotor->id, 'fecha_enrolamiento' => '2026-01-01',
            'activo' => true, 'clave' => Crypt::encryptString('anterior'),
            // Sin ID RedCap, centro ni diagnósticos: el formulario general no sería válido.
        ]);
    }

    public function test_actualiza_ambas_contrasenas_sin_validar_ni_modificar_otros_datos(): void
    {
        $datosPaciente = $this->paciente->refresh()->getRawOriginal();
        $datosUsuario = $this->paciente->usuario->getRawOriginal();

        $this->actingAs($this->promotor)
            ->putJson(route('dashboard.pacientes.updatePassword', $this->paciente), [
                'password' => 'nueva-clave', 'nombre' => 'No guardar', 'diagnosticos' => [999],
            ])
            ->assertOk()
            ->assertJson(['message' => 'La contraseña se guardó con éxito.']);

        $paciente = $this->paciente->fresh();
        $this->assertSame('nueva-clave', Crypt::decryptString($paciente->clave));
        $this->assertTrue(Hash::check('nueva-clave', $paciente->usuario->password));
        $this->assertSame(
            array_diff_key($datosPaciente, array_flip(['clave', 'updated_at'])),
            array_diff_key($paciente->getRawOriginal(), array_flip(['clave', 'updated_at'])),
        );
        $this->assertSame(
            array_diff_key($datosUsuario, array_flip(['password', 'updated_at'])),
            array_diff_key($paciente->usuario->getRawOriginal(), array_flip(['password', 'updated_at'])),
        );
        $this->assertCount(0, $paciente->diagnosticos);
    }

    public function test_rechaza_contrasenas_vacias_cortas_o_de_tipo_incorrecto(): void
    {
        foreach ([null, '', '12345', ['password']] as $password) {
            $this->actingAs($this->promotor)
                ->putJson(route('dashboard.pacientes.updatePassword', $this->paciente), compact('password'))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->assertSame('anterior', Crypt::decryptString($this->paciente->fresh()->clave));
        $this->assertTrue(Hash::check('anterior', $this->paciente->fresh()->usuario->password));
    }

    public function test_rechaza_pacientes_fuera_del_alcance_del_promotor(): void
    {
        $this->paciente->update(['promotor_id' => null]);
        $this->assertCambioDenegado();
    }

    public function test_rechaza_pacientes_inactivos(): void
    {
        $this->paciente->update(['activo' => false]);
        $this->assertCambioDenegado();
    }

    public function test_rechaza_promotores_de_peru(): void
    {
        $peru = Pais::create(['nombre' => 'Perú', 'codigo' => 'PE', 'activo' => true]);
        $this->promotor->update(['pais_id' => $peru->id]);
        $this->assertCambioDenegado();
    }

    public function test_requiere_autenticacion(): void
    {
        $this->putJson(route('dashboard.pacientes.updatePassword', $this->paciente), ['password' => 'nueva-clave'])
            ->assertUnauthorized();
    }

    public function test_revierte_la_contrasena_del_usuario_si_falla_el_guardado_del_paciente(): void
    {
        Event::listen('eloquent.updating: '.Paciente::class, function (): void {
            throw new RuntimeException('Fallo de guardado simulado.');
        });

        $this->actingAs($this->promotor)
            ->putJson(route('dashboard.pacientes.updatePassword', $this->paciente), ['password' => 'nueva-clave'])
            ->assertInternalServerError();

        $this->assertSame('anterior', Crypt::decryptString($this->paciente->fresh()->clave));
        $this->assertTrue(Hash::check('anterior', $this->paciente->fresh()->usuario->password));
    }

    public function test_guardar_datos_generales_ignora_la_contrasena_en_edicion(): void
    {
        $diagnostico = Diagnostico::create(['id' => Diagnostico::DIABETES, 'nombre' => 'Diabetes', 'activo' => true]);
        $this->actingAs($this->promotor)
            ->post(route('dashboard.pacientes.store'), [
                'id' => $this->paciente->id, 'id_externo' => 'RED-1', 'nombre' => 'Ana', 'apellido' => 'Paz',
                'fecha_nacimiento' => '01/01/1980', 'sexo' => 'F', 'documento' => '22222222',
                'telefono' => '123456', 'direccion' => 'Calle 2', 'email' => null,
                'centro_salud_id' => $this->promotor->centro_salud_id, 'promotor_id' => $this->promotor->id,
                'fecha_enrolamiento' => '01/01/2026', 'diagnosticos' => [$diagnostico->id], 'password' => 'otra-clave',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('Calle 2', $this->paciente->fresh()->direccion);
        $this->assertSame('anterior', Crypt::decryptString($this->paciente->fresh()->clave));
        $this->assertTrue(Hash::check('anterior', $this->paciente->fresh()->usuario->password));
    }

    private function assertCambioDenegado(): void
    {
        $this->actingAs($this->promotor)
            ->putJson(route('dashboard.pacientes.updatePassword', $this->paciente), ['password' => 'nueva-clave'])
            ->assertForbidden();

        $this->assertSame('anterior', Crypt::decryptString($this->paciente->fresh()->clave));
        $this->assertTrue(Hash::check('anterior', $this->paciente->fresh()->usuario->password));
    }
}
