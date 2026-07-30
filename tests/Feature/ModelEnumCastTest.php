<?php

namespace Tests\Feature;

use App\Enums\AdvertenciaClinica;
use App\Enums\MotivoInteraccion;
use App\Enums\MotivoReprogramacion;
use App\Models\Glucosa;
use App\Models\Interaccion;
use Tests\TestCase;

class ModelEnumCastTest extends TestCase
{
    public function test_models_cast_clinical_warning_and_interaction_reasons_to_enums(): void
    {
        $glucosa = new Glucosa(['advertencia' => AdvertenciaClinica::ALTA->value]);
        $interaccion = new Interaccion([
            'motivo' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'motivo_reprogramacion' => MotivoReprogramacion::NO_RESPONDE->value,
        ]);

        $this->assertSame(AdvertenciaClinica::ALTA, $glucosa->advertencia);
        $this->assertSame(MotivoInteraccion::PEDIDO_AYUDA, $interaccion->motivo);
        $this->assertSame(MotivoReprogramacion::NO_RESPONDE, $interaccion->motivo_reprogramacion);
    }
}
