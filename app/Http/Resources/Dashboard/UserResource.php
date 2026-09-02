<?php

namespace App\Http\Resources\Dashboard;

use App\Support\UserTimezone;
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
            'rol' => $this->whenLoaded('roles', fn () => new RolResource($this->roles->first())),
            'pais' => $this->whenLoaded('pais', fn () => new PaisResource($this->pais)),
            'centro_salud' => $this->whenLoaded('centroSalud', fn () => new CentroSaludResource($this->centroSalud)),
            'supervisor' => $this->whenLoaded('supervisor', fn () => new SupervisorResource($this->supervisor)),
            'numero_pacientes' => $this->when(isset($this->pacientes_count), (int) $this->pacientes_count),
            'numero_alertas' => $this->when(isset($this->alertas_count), (int) $this->alertas_count),
            'numero_accesos' => $this->accesos,
            'ultimo_acceso' => $this->ultimo_acceso
                ?->setTimezone(UserTimezone::forRequest($request))
                ->format('d/m/Y H:i'),
        ];
    }
}
