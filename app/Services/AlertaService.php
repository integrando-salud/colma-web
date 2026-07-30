<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;

class AlertaService
{
    public function queryGetAlertas(array $filters = [])
    {
        $query = Alerta::query();

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['paciente_id'])) {
            $query->where('paciente_id', $filters['paciente_id']);
        }

        if (isset($filters['revisado'])) {
            if ($filters['revisado']) {
                $query->whereNotNull('revisado');
            } else {
                $query->whereNull('revisado');
            }
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        return $query->orderBy($orderBy, $orderDir);
    }

    public function countAlertasPendientes(int $idPaciente)
    {
        return $this->queryGetAlertas([
            'paciente_id' => $idPaciente,
            'revisado' => false
        ])->count();
    }

    public function getEntidad(int $idAlerta)
    {
        $alerta = Alerta::find($idAlerta);

        switch ($alerta->entidad_tipo) {
            case 'glucosa':
                return $alerta->paciente->glucosa()->find($alerta->entidad_id);
            case 'presion_arterial':
                return $alerta->paciente->presionArterial()->find($alerta->entidad_id);
            case 'estado_animo':
                return $alerta->paciente->estadoAnimo()->find($alerta->entidad_id);
            default:
                return null;
        }
    }

    public function savePedidoAyuda(int $idPaciente)
    {
        return Alerta::create([
            'paciente_id' => $idPaciente,
            'tipo' => 'operativa',
            'descripcion' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'fecha' => now()->format('Y-m-d'),
            'fecha_contacto' => now()->addDays(3)->format('Y-m-d'),
            'recomendacion' => "Contactar y explorar motivo del pedido de ayuda. Intentar dar solución.\nCompletar formulario de alerta operativa."
        ]);
    }
}
