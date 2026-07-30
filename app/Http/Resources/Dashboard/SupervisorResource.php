<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupervisorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'pais' => $this->whenLoaded('pais', fn () => new PaisResource($this->pais)),
            'centro_salud' => $this->whenLoaded('centroSalud', fn () => new CentroSaludResource($this->centroSalud)),
        ];
    }
}
