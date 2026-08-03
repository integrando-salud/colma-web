<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->service = app(DashboardService::class);
        config()->set('app.rpp', 10);
    }

    public function test_handle_index_view_muestra_usuarios_para_roles_administrativos(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentroSalud($pais, 'Centro Buenos Aires');
        $superusuario = $this->crearUsuario($pais, $centro, User::ROL_SUPERUSUARIO);
        $this->actingAs($superusuario);

        $vista = $this->service->handleIndexView(Request::create('/', 'GET', [
            'nombre' => 'Ana',
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ]));

        $this->assertSame('Index/UsuariosIndex', $vista['name']);
        $this->assertSame('Ana', $vista['data']['filtros']['nombre']);
        $this->assertSame('nombre', $vista['data']['filtros']['orderBy']);
    }

    public function test_handle_index_view_muestra_solo_pacientes_del_promotor_y_omite_filtros_ajenos_a_esa_vista(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentroSalud($pais, 'Centro Buenos Aires');
        $promotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $otroPromotor = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $pacientePropio = $this->crearPaciente($centro, $promotor, 'Propio');
        $this->crearPaciente($centro, $otroPromotor, 'Ajeno');
        $this->actingAs($promotor);

        $vista = $this->service->handleIndexView(Request::create('/', 'GET', [
            'diagnostico' => 99,
            'orderDir' => 'asc',
        ]));

        $this->assertSame('Index/PacientesIndex', $vista['name']);
        $this->assertSame([$pacientePropio->id], $vista['data']['pacientes']->resource->pluck('id')->all());
        $this->assertArrayNotHasKey('diagnostico', $vista['data']['filtros']);
    }

    public function test_handle_index_view_falla_de_forma_explicita_para_un_usuario_sin_rol_de_panel(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentroSalud($pais, 'Centro Buenos Aires');
        $usuarioSinRol = $this->crearUsuario($pais, $centro);
        $this->actingAs($usuarioSinRol);

        $this->expectException(\UnexpectedValueException::class);

        $this->service->handleIndexView(Request::create('/'));
    }

    public function test_indice_del_panel_devuelve_403_para_un_usuario_sin_rol_compatible(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentroSalud($pais, 'Centro Buenos Aires');
        $usuarioSinRol = $this->crearUsuario($pais, $centro);
        $this->actingAs($usuarioSinRol);

        $this->get(route('dashboard.index'))->assertForbidden();
    }

    public function test_get_data_for_usuarios_page_aplica_el_alcance_del_supervisor_y_precarga_paises(): void
    {
        $paisPropio = $this->crearPais('Argentina');
        $paisAjeno = $this->crearPais('Chile');
        $centroPropio = $this->crearCentroSalud($paisPropio, 'Centro Buenos Aires');
        $centroAjeno = $this->crearCentroSalud($paisAjeno, 'Centro Santiago');
        $supervisor = $this->crearUsuario($paisPropio, $centroPropio, User::ROL_SUPERVISOR);
        $promotorPropio = $this->crearUsuario($paisPropio, $centroPropio, User::ROL_PROMOTOR, ['supervisor_id' => $supervisor->id]);
        $this->crearUsuario($paisAjeno, $centroAjeno, User::ROL_PROMOTOR);
        $this->actingAs($supervisor);

        $datos = $this->service->getDataForUsuariosPage();
        $usuarios = $datos['usuarios']->resource;

        $this->assertSame([$promotorPropio->id], $usuarios->pluck('id')->all());
        $this->assertSame([$centroPropio->id], $datos['centrosSalud']->collection->map(fn ($resource) => $resource->id)->all());
        $this->assertSame([$paisPropio->id], $datos['paises']->collection->map(fn ($resource) => $resource->id)->all());
        $this->assertTrue($usuarios->first()->relationLoaded('pais'));
        $this->assertSame($paisPropio->id, $usuarios->first()->pais->id);
        $this->assertSame([$supervisor->id], $datos['supervisores']->collection->map(fn ($resource) => $resource->id)->all());
    }

    public function test_get_data_for_pacientes_page_respeta_el_alcance_del_supervisor_y_precarga_relaciones(): void
    {
        $pais = $this->crearPais('Argentina');
        $centro = $this->crearCentroSalud($pais, 'Centro Buenos Aires');
        $supervisor = $this->crearUsuario($pais, $centro, User::ROL_SUPERVISOR);
        $promotorPropio = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR, ['supervisor_id' => $supervisor->id]);
        $promotorAjeno = $this->crearUsuario($pais, $centro, User::ROL_PROMOTOR);
        $pacientePropio = $this->crearPaciente($centro, $promotorPropio, 'Propio');
        $this->crearPaciente($centro, $promotorAjeno, 'Ajeno');
        $this->actingAs($supervisor);

        $datos = $this->service->getDataForPacientesPage(['orderDir' => 'asc']);
        $pacientes = $datos['pacientes']->resource;

        $this->assertSame([$pacientePropio->id], $pacientes->pluck('id')->all());
        $this->assertSame([$promotorPropio->id], $datos['promotores']->collection->map(fn ($resource) => $resource->id)->all());
        foreach (['centroSalud', 'promotor', 'usuario', 'diagnosticos', 'entrenamientos', 'proximosContactosProgramados', 'proximosContactosAlertas'] as $relacion) {
            $this->assertTrue($pacientes->first()->relationLoaded($relacion));
        }
    }

    private function crearPais(string $nombre): Pais
    {
        return Pais::create(['nombre' => $nombre, 'codigo' => strtoupper(substr($nombre, 0, 2)), 'activo' => true]);
    }

    private function crearCentroSalud(Pais $pais, string $nombre): CentroSalud
    {
        $centro = new CentroSalud([
            'nombre' => $nombre,
            'activo' => true,
        ]);
        $centro->pais_id = $pais->id;
        $centro->save();

        return $centro;
    }

    private function crearUsuario(Pais $pais, CentroSalud $centro, ?string $rol = null, array $atributos = []): User
    {
        static $numero = 0;
        $numero++;

        $usuario = User::create(array_merge([
            'nombre' => "Usuario $numero",
            'sexo' => 'F',
            'documento' => "DOC-$numero",
            'email' => "usuario$numero@example.test",
            'password' => 'password',
            'pais_id' => $pais->id,
            'centro_salud_id' => $centro->id,
        ], $atributos));

        if ($rol) {
            $usuario->assign($rol);
        }

        return $usuario;
    }

    private function crearPaciente(CentroSalud $centro, User $promotor, string $apellido): Paciente
    {
        static $numero = 0;
        $numero++;

        return Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => $apellido,
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => "PAC-$numero",
            'telefono' => '123456',
            'email' => "paciente$numero@example.test",
            'direccion' => 'Calle 1',
            'centro_salud_id' => $centro->id,
            'promotor_id' => $promotor->id,
            'fecha_enrolamiento' => '2026-01-01',
            'activo' => true,
        ]);
    }
}
