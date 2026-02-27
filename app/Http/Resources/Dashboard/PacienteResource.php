<?php

namespace App\Http\Resources\Dashboard;

use App\Services\AlertaService;
use App\Services\PacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class PacienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pacienteService = new PacienteService();
        $registros = $pacienteService->getDatosClinicos($this->id, 2);

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
            'observaciones' => $this->observaciones,
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
            'fecha_ultima_sincronizacion' => $this->fecha_ultima_sincronizacion?->format('d/m/Y H:i') ?? '-',
            'alertas_pendientes' => $this->numero_alertas ?? $this->alertas()->whereNull('revisado')->count(),
            'interacciones' => $this->interacciones()->orderByDesc('id')->take(3)->get(['id', 'fecha'])->map(function ($interaccion) {
                return [
                    'id' => $interaccion->id,
                    'fecha' => $interaccion->fecha->format('d/m/Y'),
                ];
            }),
            'datos_clinicos' => $registros,
            'fecha_proximo_contacto' => $this->interacciones->last()?->nueva_fecha_contacto?->format('d/m/Y'),
            'clave' => $this->clave ? Crypt::decryptString($this->clave) : null,
        ];
    }
}
