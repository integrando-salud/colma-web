<?php

namespace App\Http\Resources\Dashboard;

use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $alertaService = new AlertaService();
        $alertasPendientes = $alertaService->countAlertasPendientes($this->id);

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'fecha_nacimiento' => $this->fecha_nacimiento->format('Y-m-d'),
            'fecha_nacimiento_format' => $this->fecha_nacimiento->format('d/m/Y'),
            'edad' => $this->fecha_nacimiento->age,
            'sexo' => $this->sexo,
            'sexo_descripcion' => match ($this->sexo) {
                'M' => 'Masculino',
                'F' => 'Femenino',
            },
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'telefono2' => $this->telefono2,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'referencia' => $this->referencia,
            'centro_salud' => new CentroSaludResource($this->centroSalud),
            'promotor' => new UserResource($this->promotor),
            'fecha_enrolamiento' => $this->fecha_enrolamiento->format('Y-m-d'),
            'fecha_enrolamiento_format' => $this->fecha_enrolamiento->format('d/m/Y'),
            'diagnosticos' => DiagnosticoResource::collection($this->diagnosticos),
            'paquete' => new PaqueteResource($this->paquete),
            'usuario' => new UserResource($this->usuario),
            'sesiones' => [
                'total' => 5,
                'completadas' => 4,
            ],
            'activo' => $this->activo,
            'estado' => $this->activo ? 'Activo' : 'Inactivo',
            'alertas_pendientes' => $alertasPendientes,
            'interacciones' => $this->interacciones()->orderByDesc('id')->take(3)->get(['id', 'fecha'])->map(function ($interaccion) {
                return [
                    'id' => $interaccion->id,
                    'fecha' => $interaccion->fecha->format('d/m/Y'),
                ];
            }),
        ];
    }
}
