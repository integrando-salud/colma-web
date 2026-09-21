<?php

namespace App\Http\Resources\API;

use App\Models\User;
use App\Support\UserTimezone;
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
        // La información asistencial de un paciente pertenece a su perfil
        // clínico. El usuario de acceso puede provenir de datos históricos o
        // de una carga de prueba y tener asociaciones distintas.
        $centroSalud = $this->paciente?->centroSalud;
        $pais = $centroSalud?->pais;

        return [
            'id' => $this->id,
            'documento' => $this->documento,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'sexo' => $this->sexo,
            'pais' => [
                'id' => $pais?->id ?? null,
                'nombre' => $pais?->nombre ?? null,
            ],
            'centro_salud' => [
                'id' => $centroSalud?->id ?? null,
                'nombre' => $centroSalud?->nombre ?? null,
            ],
            'primer_login' => $this->paciente?->fecha_primer_login_app
                ?->setTimezone($this->resource instanceof User
                    ? UserTimezone::forUser($this->resource)
                    : UserTimezone::forRequest($request))
                ->format('Y-m-d H:i:s') ?? null,
        ];
    }
}
