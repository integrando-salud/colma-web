<?php

namespace Tests\Unit;

use App\Models\CentroSalud;
use App\Models\Diagnostico;
use App\Models\Paciente;
use App\Models\Pais;
use App\Services\TratamientoService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TratamientoServiceTest extends TestCase
{
    private string $tratamientosDirname = 'tratamientos-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.multimedia.tratamientos_dirname', $this->tratamientosDirname);
        config()->set('filesystems.multimedia.tratamientos_diabetes_dirname', 'DBT');
        config()->set('filesystems.multimedia.tratamientos_hipertension_dirname', 'HTA');
        config()->set('filesystems.multimedia.tratamientos_diabetes_hipertension_dirname', 'DBT_HTA');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path("app/private/$this->tratamientosDirname"));

        parent::tearDown();
    }

    public function test_obtiene_el_tratamiento_de_diabetes_con_semanas_sesiones_y_archivos_ordenados(): void
    {
        $basePath = storage_path("app/private/$this->tratamientosDirname/AR/DBT");
        File::makeDirectory("$basePath/semana-10/sesion-2", 0755, true);
        File::makeDirectory("$basePath/semana-2/sesion-10", 0755, true);
        File::makeDirectory("$basePath/semana-2/sesion-1", 0755, true);
        File::makeDirectory("$basePath/semana-borrador/sesion-3", 0755, true);
        File::makeDirectory("$basePath/semana-2/material-adicional", 0755, true);
        File::put("$basePath/semana-2/sesion-1/zeta.pdf", 'pdf');
        File::put("$basePath/semana-2/sesion-1/Foto.png", base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL2WQAAAABJRU5ErkJggg=='
        ));

        $tratamiento = app(TratamientoService::class)->getTratamientoPaciente(
            $this->pacienteConDiagnosticos([Diagnostico::DIABETES])
        );

        $this->assertSame('DIABETES', $tratamiento['tratamiento']['tipo']);
        $this->assertSame('Diabetes', $tratamiento['tratamiento']['nombre']);
        $this->assertSame([2, 10], array_column($tratamiento['tratamiento']['semanas'], 'id'));
        $this->assertSame([1, 10], array_column($tratamiento['tratamiento']['semanas'][0]['sesiones'], 'id'));
        $this->assertSame(
            ['Foto.png', 'zeta.pdf'],
            array_column($tratamiento['tratamiento']['semanas'][0]['sesiones'][0]['content'], 'title')
        );
        $this->assertSame(
            ['IMAGEN', 'ARCHIVO'],
            array_column($tratamiento['tratamiento']['semanas'][0]['sesiones'][0]['content'], 'type')
        );
        $this->assertSame(
            route('multimedia', ['path' => "$this->tratamientosDirname/AR/DBT/semana-2/sesion-1/Foto.png"]),
            $tratamiento['tratamiento']['semanas'][0]['sesiones'][0]['content'][0]['link']
        );
    }

    public function test_obtiene_el_tratamiento_de_hipertension_y_el_combinado(): void
    {
        File::makeDirectory(storage_path("app/private/$this->tratamientosDirname/AR/HTA"), 0755, true);
        File::makeDirectory(storage_path("app/private/$this->tratamientosDirname/AR/DBT_HTA"), 0755, true);

        $service = app(TratamientoService::class);

        $this->assertSame('HTA', $service->getTratamientoPaciente(
            $this->pacienteConDiagnosticos([Diagnostico::HIPERTENSION_ARTERIAL])
        )['tratamiento']['tipo']);
        $this->assertSame('DBT_HTA', $service->getTratamientoPaciente(
            $this->pacienteConDiagnosticos([Diagnostico::DIABETES, Diagnostico::HIPERTENSION_ARTERIAL])
        )['tratamiento']['tipo']);
    }

    public function test_no_retorna_tratamiento_ni_sesiones_para_diagnosticos_sin_configuracion(): void
    {
        $paciente = $this->pacienteConDiagnosticos([Diagnostico::DEPRESION]);
        $service = app(TratamientoService::class);

        $this->assertNull($service->getTratamientoPaciente($paciente));
        $this->assertSame(0, $service->getCantidadSesionesPaciente($paciente));
    }

    public function test_retorna_un_tratamiento_sin_semanas_y_cero_sesiones_si_no_hay_material(): void
    {
        $paciente = $this->pacienteConDiagnosticos([Diagnostico::DIABETES]);
        $service = app(TratamientoService::class);

        $this->assertSame([], $service->getTratamientoPaciente($paciente)['tratamiento']['semanas']);
        $this->assertSame(0, $service->getCantidadSesionesPaciente($paciente));
    }

    public function test_cuenta_solo_las_sesiones_dentro_de_las_semanas_del_tratamiento(): void
    {
        $basePath = storage_path("app/private/$this->tratamientosDirname/AR/DBT");
        File::makeDirectory("$basePath/semana-1/sesion-1", 0755, true);
        File::makeDirectory("$basePath/semana-1/sesion-2", 0755, true);
        File::makeDirectory("$basePath/semana-2/sesion-1", 0755, true);
        File::makeDirectory("$basePath/semana-borrador/sesion-3", 0755, true);
        File::makeDirectory("$basePath/semana-1/material-adicional", 0755, true);

        $cantidad = app(TratamientoService::class)->getCantidadSesionesPaciente(
            $this->pacienteConDiagnosticos([Diagnostico::DIABETES])
        );

        $this->assertSame(3, $cantidad);
    }

    public function test_lanza_una_excepcion_si_no_se_puede_determinar_el_pais_del_paciente(): void
    {
        $paciente = new Paciente();
        $paciente->setRelation('diagnosticos', new Collection());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se pudo determinar el país del paciente');

        app(TratamientoService::class)->getTratamientoPaciente($paciente);
    }

    public function test_no_cuenta_sesiones_si_no_se_puede_determinar_el_pais_del_paciente(): void
    {
        $paciente = new Paciente();
        $paciente->setRelation('diagnosticos', new Collection());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se pudo determinar el país del paciente');

        app(TratamientoService::class)->getCantidadSesionesPaciente($paciente);
    }

    /**
     * Crea un paciente en memoria con los diagnósticos y país requeridos por el servicio.
     *
     * @param array<int> $diagnosticosIds
     */
    private function pacienteConDiagnosticos(array $diagnosticosIds): Paciente
    {
        $diagnosticos = collect($diagnosticosIds)->map(function (int $id) {
            $diagnostico = new Diagnostico();
            $diagnostico->id = $id;

            return $diagnostico;
        });

        $paciente = new Paciente();
        $paciente->setRelation('diagnosticos', $diagnosticos);
        $paciente->setRelation('centroSalud', new CentroSalud(['pais_id' => 1]));
        $paciente->centroSalud->setRelation('pais', new Pais(['codigo' => 'AR']));

        return $paciente;
    }
}
