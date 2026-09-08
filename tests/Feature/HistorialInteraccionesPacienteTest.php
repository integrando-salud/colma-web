<?php

namespace Tests\Feature;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\CentroSalud;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialInteraccionesPacienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_en_el_historial_los_intentos_de_contacto_asociados_a_alertas(): void
    {
        $this->seed(RolesSeeder::class);
        $pais = Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $centro = CentroSalud::create(['nombre' => 'Centro de prueba', 'pais_id' => $pais->id, 'activo' => true]);
        $usuario = User::create([
            'nombre' => 'Promotora de prueba',
            'sexo' => 'F',
            'documento' => 'USR-HISTORIAL',
            'email' => 'historial@example.test',
            'password' => 'password',
            'pais_id' => $pais->id,
            'centro_salud_id' => $centro->id,
        ]);
        $usuario->assign(User::ROL_PROMOTOR);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'De prueba',
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => 'PAC-HISTORIAL',
            'telefono' => '123456',
            'email' => 'paciente-historial@example.test',
            'direccion' => 'Calle de prueba',
            'centro_salud_id' => $centro->id,
            'promotor_id' => $usuario->id,
            'fecha_enrolamiento' => '2026-01-01',
            'activo' => true,
        ]);
        $alerta = Alerta::create([
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'descripcion' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'fecha' => '2026-09-01',
            'recomendacion' => 'Contactar al paciente.',
        ]);
        $interaccion = Interaccion::create([
            'paciente_id' => $paciente->id,
            'user_id' => $usuario->id,
            'alerta_id' => $alerta->id,
            'contactado' => false,
            'fecha' => '2026-09-02',
            'hora' => '10:00',
            'modalidad' => Interaccion::MODALIDADES[0],
            'motivo' => MotivoInteraccion::PEDIDO_AYUDA,
            'nueva_fecha_contacto' => '2026-09-03',
        ]);

        $this->actingAs($usuario)
            ->withHeader('X-Inertia', 'true')
            ->get(route('dashboard.pacientes.interacciones', $paciente))
            ->assertOk()
            ->assertJsonPath('props.interacciones.data.0.id', $interaccion->id)
            ->assertJsonPath('props.interacciones.data.0.contactado', 0)
            ->assertJsonPath('props.interacciones.data.0.alerta.id', $alerta->id)
            ->assertJsonPath('props.origenInteraccion', 'contactos');
    }
}
