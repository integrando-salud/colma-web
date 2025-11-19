<?php

namespace App\Services;

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
}
