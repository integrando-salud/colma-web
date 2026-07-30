<?php

namespace App\Http\Resources\Dashboard;

use App\Services\PacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;

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
        $entrenamientoActual = $this->resource->relationLoaded('entrenamientos')
            ? $this->entrenamientos->first()
            : $this->entrenamientos()->where('activo', true)->latest()->first();
        $fechaProximoContacto = $this->nueva_fecha_contacto
            ? Carbon::parse($this->nueva_fecha_contacto)
            : $pacienteService->getFechaProximoContacto($this->resource);
        $proximosContactos = $pacienteService->getFechasProximosContactos($this->resource);
        $diasPostFechaProximoContacto = (int) $fechaProximoContacto?->diffInDays(now()) ?? 0;

        return [
            'id' => $this->id,
            'id_externo' => $this->id_externo,
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
            'usuario' => new UserResource($this->usuario),
            'sesiones' => $entrenamientoActual ? [
                'total' => $entrenamientoActual->sesiones,
                'completadas' => $entrenamientoActual->sesiones_completadas,
            ] : null,
            'activo' => $this->activo,
            'estado' => $this->activo ? 'Activo' : 'Inactivo',
            'fecha_ultima_sincronizacion' => $this->fecha_ultima_sincronizacion?->format('d/m/Y H:i') ?? '-',
            'alertas_pendientes' => $this->numero_alertas ?? $this->alertas()->whereNull('revisado')->count(),
            'proximos_contactos' => $proximosContactos,
            'fecha_proximo_contacto' => $fechaProximoContacto?->format('d/m/Y'),
            'fecha_proximo_contacto_dias' => $diasPostFechaProximoContacto,
            'clave' => $this->clave ? Crypt::decryptString($this->clave) : null,
            'test' => $this->test,
        ];
    }
}
