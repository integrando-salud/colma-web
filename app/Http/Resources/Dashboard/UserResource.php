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
            'nombre' => $this->nombre,
            'sexo' => $this->sexo,
            'sexo_descripcion' => match ($this->sexo) {
                'M' => 'Masculino',
                'F' => 'Femenino',
            },
            'documento' => $this->documento,
            'email' => $this->email,
            'rol' => $this->roles ? new RolResource($this->roles->first()) : null,
            'pais' => $this->pais ? new PaisResource($this->pais) : null,
            'centro_salud' => $this->centroSalud ? new CentroSaludResource($this->centroSalud) : null,
            'supervisor' => $this->supervisor ? new UserResource($this->supervisor) : null,
            'numero_pacientes' => 0,
            'numero_accesos' => $this->accesos,
            'ultimo_acceso' => $this->ultimo_acceso?->format('d/m/Y H:i'),
        ];
    }
}
