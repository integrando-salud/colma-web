<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->roles ? new RolResource($this->roles->first()) : null,
            'pais' => $this->pais ? new PaisResource($this->pais) : null,
            'centro_salud' => $this->centroSalud ? new CentroSaludResource($this->centroSalud) : null,
            'supervisor' => $this->supervisor ? new UserResource($this->supervisor) : null,
            'numero_pacientes' => 0,
            'ultimo_ingreso' => $this->last_login?->format('d/m/Y H:i'),
        ];
    }
}
