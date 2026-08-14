<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Interaccion;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteraccionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->alerta) {
            $alertaService = new AlertaService;
            $entidad = $alertaService->getEntidad($this->alerta);
            $alertaData = [
                'id' => $this->alerta->id,
                'descripcion' => $this->alerta->descripcion,
                'fecha' => $this->alerta->fecha->format('d/m/Y'),
                'valor' => $entidad?->valor ?? null,
                'rango_normal' => $entidad ? $entidad::RANGO_NORMAL : null,
            ];
        }

        return [
            'id' => $this->id,
            'paciente' => [
                'id' => $this->paciente_id,
                'nombre' => trim($this->paciente->nombre.' '.$this->paciente->apellido),
            ],
            'usuario' => [
                'id' => $this->user_id,
                'nombre' => $this->user->nombre,
            ],
            'alerta' => $this->alerta ? $alertaData : null,
            'contactado' => (int) $this->contactado,
            'contactado_descripcion' => Interaccion::DESCRIPCION_CONTACTO[$this->contactado],
            'fecha' => $this->fecha->format('d/m/Y'),
            'hora' => $this->hora->format('H:i'),
            'hora_am_pm' => $this->hora->format('h:i A'),
            'modalidad' => $this->modalidad,
            'motivo' => $this->motivo,
            'observaciones' => $this->observaciones,
            'fecha_proximo_contacto' => $this->nueva_fecha_contacto?->format('d/m/Y'),
            'motivo_reprogramacion' => $this->motivo_reprogramacion,
            'fecha_cita' => $this->fecha_cita?->format('d/m/Y'),
            'hora_cita' => $this->hora_cita?->format('H:i'),
            'hora_cita_am_pm' => $this->hora_cita?->format('h:i A'),
            'lugar_cita' => $this->lugar_cita,
            'lugar_cita_otro' => $this->lugar_cita_otro,
            'fecha_cita_final' => $this->fecha_cita_final?->format('d/m/Y'),
            'hora_cita_final' => $this->hora_cita_final?->format('H:i'),
            'hora_cita_final_am_pm' => $this->hora_cita_final?->format('h:i A'),
            'seguimiento_pregunta_a' => $this->seguimiento_pregunta_a,
            'seguimiento_pregunta_b' => $this->seguimiento_pregunta_b,
            'seguimiento_pregunta_c' => $this->seguimiento_pregunta_c,
            'seguimiento_pregunta_d' => $this->seguimiento_pregunta_d,
            'seguimiento_pregunta_e' => $this->seguimiento_pregunta_e,
            'fecha_reentrenamiento' => $this->fecha_reentrenamiento?->format('d/m/Y'),
            'hora_reentrenamiento' => $this->hora_reentrenamiento?->format('H:i'),
            'lugar_reentrenamiento' => $this->lugar_reentrenamiento,
            'lugar_reentrenamiento_otro' => $this->lugar_reentrenamiento_otro,
            'razon_contacto_participante' => $this->razon_contacto_participante,
            'razon_contacto_participante_otro' => $this->razon_contacto_participante_otro,
            'contacto_participante_acciones' => $this->contacto_participante_acciones,
            'nueva_fecha_contacto' => $this->nueva_fecha_contacto?->format('d/m/Y'),
            'no_adherencia_pregunta_a' => $this->no_adherencia_pregunta_a,
            'no_adherencia_otro' => $this->no_adherencia_otro,
            'no_adherencia_pregunta_b' => $this->no_adherencia_pregunta_b,
            'no_adherencia_pregunta_c' => $this->no_adherencia_pregunta_c?->format('d/m/Y'),
            'problemas_conexion_pregunta_a' => $this->problemas_conexion_pregunta_a,
            'problemas_conexion_otro' => $this->problemas_conexion_otro,
            'problemas_conexion_pregunta_b' => $this->problemas_conexion_pregunta_b,
            'problemas_conexion_pregunta_c' => $this->problemas_conexion_pregunta_c?->format('d/m/Y'),
            'alerta_clinica_pregunta_a' => $this->alerta_clinica_pregunta_a,
            'alerta_clinica_pregunta_b' => $this->alerta_clinica_pregunta_b,
            'alerta_clinica_pregunta_c' => $this->alerta_clinica_pregunta_c,
            'alerta_clinica_pregunta_d' => $this->alerta_clinica_pregunta_d,
            'alerta_clinica_pregunta_e' => $this->alerta_clinica_pregunta_e,
            'alerta_clinica_pregunta_f' => $this->alerta_clinica_pregunta_f,
            'pedido_ayuda_pregunta_a' => $this->pedido_ayuda_pregunta_a,
            'pedido_ayuda_pregunta_b' => $this->pedido_ayuda_pregunta_b,
            'pedido_ayuda_pregunta_c' => $this->pedido_ayuda_pregunta_c,
            'pedido_ayuda_pregunta_d' => $this->pedido_ayuda_pregunta_d?->format('d/m/Y'),
        ];
    }
}
