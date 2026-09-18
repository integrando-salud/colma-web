<?php

namespace App\Http\Resources\Dashboard;

use App\Services\TratamientoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntrenamientoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resumenProgreso = app(TratamientoService::class)->getResumenProgresoEntrenamiento($this->resource);

        return [
            'id' => $this->id,
            'paciente_id' => $this->paciente_id,
            'fecha' => $this->fecha->format('d/m/Y'),
            'sesiones' => $resumenProgreso['sesiones_totales'],
            'seguimientos_mensuales' => $this->seguimientos_mensuales,
            'sesiones_completadas' => $resumenProgreso['sesiones_completadas'],
            'seguimiento_dos_semanas_inicio' => $this->seguimiento_dos_semanas_inicio->format('d/m/Y'),
            'seguimiento_dos_semanas_fin' => $this->seguimiento_dos_semanas_fin->format('d/m/Y'),
            'llamada_mensual_1_inicio' => $this->llamada_mensual_1_inicio->format('d/m/Y'),
            'llamada_mensual_1_fin' => $this->llamada_mensual_1_fin->format('d/m/Y'),
            'llamada_mensual_2_inicio' => $this->llamada_mensual_2_inicio->format('d/m/Y'),
            'llamada_mensual_2_fin' => $this->llamada_mensual_2_fin->format('d/m/Y'),
            'llamada_mensual_3_inicio' => $this->llamada_mensual_3_inicio->format('d/m/Y'),
            'llamada_mensual_3_fin' => $this->llamada_mensual_3_fin->format('d/m/Y'),
            'llamada_mensual_4_inicio' => $this->llamada_mensual_4_inicio->format('d/m/Y'),
            'llamada_mensual_4_fin' => $this->llamada_mensual_4_fin->format('d/m/Y'),
            'llamada_mensual_5_inicio' => $this->llamada_mensual_5_inicio->format('d/m/Y'),
            'llamada_mensual_5_fin' => $this->llamada_mensual_5_fin->format('d/m/Y'),
            'agendar_cita_final_inicio' => $this->agendar_cita_final_inicio->format('d/m/Y'),
            'agendar_cita_final_fin' => $this->agendar_cita_final_fin->format('d/m/Y'),
            'activo' => $this->activo,
            'user_id' => $this->user_id,
        ];
    }
}
