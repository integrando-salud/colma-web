<?php

namespace App\Services;

use App\Models\Interaccion;

class InteraccionService
{
    public function storeInteraccion(array $data)
    {
        return Interaccion::create([
            'paciente_id' => $data['paciente_id'],
            'user_id' => auth()->user()->id,
            'alerta_id' => data_get($data, 'alerta_id'),
            'alerta_tipo' => data_get($data, 'alerta_tipo'),
            'contactado' => $data['contactado'],
            'razon_no_contacto' => data_get($data, 'razon_no_contacto'),
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'modalidad' => $data['modalidad'],
            'motivo' => $data['motivo'],
            'observaciones' => data_get($data, 'observaciones'),
            'accion' => data_get($data, 'accion'),
            'fecha_reprogramacion' => data_get($data, 'fecha_reprogramacion'),
            'motivo_reprogramacion' => data_get($data, 'motivo_reprogramacion'),
        ]);
    }
}
