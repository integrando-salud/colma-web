<?php

namespace Tests\Feature;

use App\Http\Middleware\ProhibirPromotoresPeru;
use App\Models\CentroSalud;
use App\Models\Pais;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProhibirPromotoresPeruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_el_promotor_de_peru_no_puede_crear_pacientes(): void
    {
        $promotor = $this->crearPromotorPeru();
        $this->actingAs($promotor);

        try {
            $this->ejecutarMiddleware([]);
            $this->fail('El middleware debió impedir la creación del paciente.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_el_promotor_de_peru_puede_enviar_la_edicion_de_un_paciente(): void
    {
        $promotor = $this->crearPromotorPeru();
        $this->actingAs($promotor);
        $paciente = $this->crearPaciente($promotor);

        $response = $this->ejecutarMiddleware(['id' => $paciente->id]);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function ejecutarMiddleware(array $datos)
    {
        $request = Request::create('/pacientes/guardar', 'POST', $datos);

        return app(ProhibirPromotoresPeru::class)->handle($request, fn () => response('ok'));
    }

    private function crearPromotorPeru(): User
    {
        Pais::create(['nombre' => 'Argentina', 'codigo' => 'AR', 'activo' => true]);
        $peru = Pais::create(['nombre' => 'Perú', 'codigo' => 'PE', 'activo' => true]);
        $centro = CentroSalud::create(['nombre' => 'Centro Perú', 'activo' => true]);
        $centro->pais()->associate($peru)->save();

        $promotor = User::create([
            'nombre' => 'Promotor Perú',
            'sexo' => 'F',
            'documento' => 'PERU-1',
            'email' => 'promotor@example.test',
            'password' => 'secret',
            'pais_id' => $peru->id,
            'centro_salud_id' => $centro->id,
        ]);
        $promotor->assign(User::ROL_PROMOTOR);

        return $promotor;
    }

    private function crearPaciente(User $promotor): Paciente
    {
        return Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Perú',
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => 'PAC-PERU-1',
            'telefono' => '123456',
            'email' => 'paciente@example.test',
            'direccion' => 'Calle 1',
            'centro_salud_id' => $promotor->centro_salud_id,
            'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => '2026-01-01',
            'activo' => true,
        ]);
    }
}
