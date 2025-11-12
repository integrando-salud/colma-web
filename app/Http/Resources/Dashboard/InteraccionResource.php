<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Interaccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteraccionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'paciente' => [
                'id' => $this->paciente_id,
                'nombre' => trim($this->paciente->nombre . ' ' . $this->paciente->apellido),
            ],
            'usuario' => [
                'id' => $this->user_id,
                'nombre' => $this->user->nombre,
            ],
            'alerta_id' => $this->alerta_id,
            'alerta_tipo' => $this->alerta_tipo,
            'contactado' => $this->contactado,
            'contactado_descripcion' => Interaccion::DESCRIPCION_CONTACTO[$this->contactado],
            'razon_no_contacto' => $this->razon_no_contacto,
            'fecha' => $this->fecha->format('d/m/Y'),
            'hora' => $this->hora->format('H:i'),
            'modalidad' => $this->modalidad,
            'motivo' => $this->motivo,
            'observaciones' => $this->observaciones,
            'accion' => $this->accion,
            'fecha_reprogramacion' => $this->fecha_reprogramacion?->format('d/m/Y'),
            'motivo_reprogramacion' => $this->motivo_reprogramacion,
        ];
    }
}
