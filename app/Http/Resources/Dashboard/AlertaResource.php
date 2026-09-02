<?php

namespace App\Http\Resources\Dashboard;

use App\Services\AlertaService;
use App\Support\UserTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $alertaService = new AlertaService;
        $entidad = $alertaService->getEntidad($this->resource);

        return [
            'id' => $this->id,
            'paciente' => $this->paciente->only(['id', 'nombre', 'apellido', 'activo']),
            'tipo' => $this->tipo,
            'valor' => $entidad?->valor ?? $entidad?->valor_mgdl ?? null,
            'rango_normal' => $entidad ? $entidad::RANGO_NORMAL : null,
            'descripcion' => $this->descripcion,
            'recomendacion' => $this->recomendacion,
            'fecha' => $this->fecha->format('d/m/Y'),
            'fecha_contacto' => $this->fecha_contacto?->format('d/m/Y') ?? null,
            'fecha_revisado' => $this->revisado
                ?->setTimezone(UserTimezone::forRequest($request))
                ->format('d/m/Y') ?? null,
            'usuario' => [
                'id' => $this->user?->id ?? null,
                'nombre' => $this->user?->nombre ?? null,
            ],
            'interaccion_id' => $this->ultimaInteraccionContactada?->id,
        ];
    }
}
