<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Interaccion;
use Illuminate\Support\Facades\DB;
use Throwable;

class InteraccionService
{
    public function storeInteraccion(array $data)
    {
        try {
            DB::beginTransaction();

            $alertaId = data_get($data, 'alerta_id');
            Interaccion::create([
                'paciente_id' => $data['paciente_id'],
                'user_id' => auth()->user()->id,
                'alerta_id' => $alertaId,
                'contactado' => $data['contactado'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'modalidad' => $data['modalidad'],
                'motivo' => $data['motivo'],
                'observaciones' => data_get($data, 'observaciones'),
                'motivo_reprogramacion' => data_get($data, 'motivo_reprogramacion'),
                'fecha_cita' => data_get($data, 'fecha_cita'),
                'lugar_cita' => data_get($data, 'lugar_cita'),
                'lugar_cita_otro' => data_get($data, 'lugar_cita_otro'),
                'fecha_cita_final' => data_get($data, 'fecha_cita_final'),
                'hora_cita_final' => data_get($data, 'hora_cita_final'),
                'lugar_cita_final' => data_get($data, 'lugar_cita_final'),
                'lugar_cita_final_otro' => data_get($data, 'lugar_cita_final_otro'),
                'seguimiento_pregunta_a' => data_get($data, 'seguimiento_pregunta_a'),
                'seguimiento_pregunta_b' => data_get($data, 'seguimiento_pregunta_b'),
                'seguimiento_pregunta_c' => data_get($data, 'seguimiento_pregunta_c'),
                'seguimiento_pregunta_d' => data_get($data, 'seguimiento_pregunta_d'),
                'seguimiento_pregunta_e' => data_get($data, 'seguimiento_pregunta_e'),
                'seguimiento_pregunta_f' => data_get($data, 'seguimiento_pregunta_f'),
                'razon_contacto_participante' => data_get($data, 'razon_contacto_participante'),
                'razon_contacto_participante_otro' => data_get($data, 'razon_contacto_participante_otro'),
                'contacto_participante_acciones' => data_get($data, 'contacto_participante_acciones'),
                'nueva_fecha_contacto' => data_get($data, 'nueva_fecha_contacto'),
                'no_adherencia_pregunta_a' => data_get($data, 'no_adherencia_pregunta_a'),
                'no_adherencia_otro' => data_get($data, 'no_adherencia_otro'),
                'no_adherencia_pregunta_b' => data_get($data, 'no_adherencia_pregunta_b'),
                'no_adherencia_pregunta_c' => data_get($data, 'no_adherencia_pregunta_c'),
                'problemas_conexion_pregunta_a' => data_get($data, 'problemas_conexion_pregunta_a'),
                'problemas_conexion_otro' => data_get($data, 'problemas_conexion_otro'),
                'problemas_conexion_pregunta_b' => data_get($data, 'problemas_conexion_pregunta_b'),
                'problemas_conexion_pregunta_c' => data_get($data, 'problemas_conexion_pregunta_c'),
                'alerta_clinica_pregunta_a' => data_get($data, 'alerta_clinica_pregunta_a'),
                'alerta_clinica_pregunta_b' => data_get($data, 'alerta_clinica_pregunta_b'),
                'alerta_clinica_pregunta_c' => data_get($data, 'alerta_clinica_pregunta_c'),
                'alerta_clinica_pregunta_d' => data_get($data, 'alerta_clinica_pregunta_d'),
                'alerta_clinica_pregunta_e' => data_get($data, 'alerta_clinica_pregunta_e'),
                'alerta_clinica_pregunta_f' => data_get($data, 'alerta_clinica_pregunta_f'),
                'pedido_ayuda_pregunta_a' => data_get($data, 'pedido_ayuda_pregunta_a'),
                'pedido_ayuda_otro' => data_get($data, 'pedido_ayuda_otro'),
                'pedido_ayuda_pregunta_b' => data_get($data, 'pedido_ayuda_pregunta_b'),
                'pedido_ayuda_pregunta_c' => data_get($data, 'pedido_ayuda_pregunta_c'),
                'pedido_ayuda_pregunta_d' => data_get($data, 'pedido_ayuda_pregunta_d'),
            ]);

            if ($alertaId) {
                Alerta::where('id', $alertaId)->update([
                    'revisado' => $data['contactado'] ? now() : null,
                    'user_id' => auth()->user()->id,
                ]);
            }

            DB::commit();

            return true;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function deleteInteraccion(Interaccion $interaccion)
    {
        return $interaccion->delete();
    }
}
