<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use App\Models\Cuestionario;
use App\Models\Meta;
use App\Models\MultimediaRead;
use App\Models\Paciente;
use App\Models\Pais;
use App\Models\User;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    public function test_inverse_location_relationships_use_the_expected_models_and_keys(): void
    {
        $this->assertInstanceOf(CentroSalud::class, (new Pais())->centrosSalud()->getRelated());
        $this->assertSame('pais_id', (new Pais())->centrosSalud()->getForeignKeyName());

        $this->assertInstanceOf(Paciente::class, (new CentroSalud())->pacientes()->getRelated());
        $this->assertInstanceOf(User::class, (new CentroSalud())->users()->getRelated());
        $this->assertSame('centro_salud_id', (new CentroSalud())->pacientes()->getForeignKeyName());
        $this->assertSame('centro_salud_id', (new CentroSalud())->users()->getForeignKeyName());
    }

    public function test_paciente_exposes_all_synced_data_relationships(): void
    {
        $paciente = new Paciente();

        $this->assertInstanceOf(Cuestionario::class, $paciente->cuestionarios()->getRelated());
        $this->assertInstanceOf(Meta::class, $paciente->metas()->getRelated());
        $this->assertInstanceOf(MultimediaRead::class, $paciente->multimediaReads()->getRelated());
    }
}
