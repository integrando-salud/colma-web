<?php

namespace App\Http\Resources\Dashboard;

use App\Services\UsuarioService;
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
        $usuarioService = new UsuarioService();

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
            'numero_pacientes' => $usuarioService->countPacientes($this->id),
            'numero_accesos' => $this->accesos,
            'numero_alertas' => $usuarioService->countAlertas($this->id),
            'ultimo_acceso' => $this->ultimo_acceso?->format('d/m/Y H:i'),
        ];
    }
}
