<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfoUsuarioResource extends JsonResource
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
            'documento' => $this->documento,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'sexo' => $this->sexo,
            'pais' => [
                'id' => $this->pais?->id ?? null,
                'nombre' => $this->pais?->nombre ?? null,
            ],
            'centro_salud' => [
                'id' => $this->centroSalud?->id ?? null,
                'nombre' => $this->centroSalud?->nombre ?? null,
            ],
            'primer_login' => $this->paciente?->fecha_primer_login_app?->format('Y-m-d H:i:s') ?? null,
        ];
    }
}
