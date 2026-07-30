<?php

namespace Tests\Unit;

use App\Models\CentroSalud;
use App\Models\Diagnostico;
use App\Models\Paciente;
use App\Models\Pais;
use App\Services\TratamientoService;
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
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path("app/private/$this->tratamientosDirname"));

        parent::tearDown();
    }

    public function test_cuenta_solo_las_sesiones_dentro_de_las_semanas_del_tratamiento(): void
    {
        $basePath = storage_path("app/private/$this->tratamientosDirname/AR/DBT");
        File::makeDirectory("$basePath/semana-1/sesion-1", 0755, true);
        File::makeDirectory("$basePath/semana-1/sesion-2", 0755, true);
        File::makeDirectory("$basePath/semana-2/sesion-1", 0755, true);
        File::makeDirectory("$basePath/semana-borrador/sesion-3", 0755, true);
        File::makeDirectory("$basePath/semana-1/material-adicional", 0755, true);

        $diagnostico = new Diagnostico();
        $diagnostico->id = Diagnostico::DIABETES;

        $paciente = new Paciente();
        $paciente->setRelation('diagnosticos', new Collection([$diagnostico]));
        $paciente->setRelation('centroSalud', new CentroSalud([
            'pais_id' => 1,
        ]));
        $paciente->centroSalud->setRelation('pais', new Pais(['codigo' => 'AR']));

        $cantidad = app(TratamientoService::class)->getCantidadSesionesPaciente($paciente);

        $this->assertSame(3, $cantidad);
    }
}
