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

            $interaccion = empty($data['id']) ? new Interaccion() : Interaccion::find($data['id']);
            $interaccion->paciente_id = $data['paciente_id'];
            $interaccion->user_id = auth()->user()->id;
            $interaccion->alerta_id = $alertaId;
            $interaccion->contactado = $data['contactado'];
            $interaccion->fecha = implode('-', array_reverse(explode('/', $data['fecha'])));
            $interaccion->hora = $data['hora'];
            $interaccion->modalidad = $data['modalidad'];
            $interaccion->motivo = $data['motivo'];
            $interaccion->observaciones = data_get($data, 'observaciones');
            $interaccion->motivo_reprogramacion = data_get($data, 'motivo_reprogramacion');
            $interaccion->fecha_cita = !empty($data['fecha_cita'])
                ? implode('-', array_reverse(explode('/', $data['fecha_cita'])))
                : null;
            $interaccion->lugar_cita = data_get($data, 'lugar_cita');
            $interaccion->lugar_cita_otro = data_get($data, 'lugar_cita_otro');
            $interaccion->fecha_cita_final = !empty($data['fecha_cita_final'])
                ? implode('-', array_reverse(explode('/', $data['fecha_cita_final'])))
                : null;
            $interaccion->hora_cita_final = data_get($data, 'hora_cita_final');
            $interaccion->lugar_cita_final = data_get($data, 'lugar_cita_final');
            $interaccion->lugar_cita_final_otro = data_get($data, 'lugar_cita_final_otro');
            $interaccion->seguimiento_pregunta_a = data_get($data, 'seguimiento_pregunta_a');
            $interaccion->seguimiento_pregunta_b = data_get($data, 'seguimiento_pregunta_b');
            $interaccion->seguimiento_pregunta_c = data_get($data, 'seguimiento_pregunta_c');
            $interaccion->seguimiento_pregunta_d = data_get($data, 'seguimiento_pregunta_d');
            $interaccion->seguimiento_pregunta_e = data_get($data, 'seguimiento_pregunta_e');
            $interaccion->seguimiento_pregunta_f = !empty($data['seguimiento_pregunta_f'])
                ? implode('-', array_reverse(explode('/', $data['seguimiento_pregunta_f'])))
                : null;
            $interaccion->razon_contacto_participante = data_get($data, 'razon_contacto_participante');
            $interaccion->razon_contacto_participante_otro = data_get($data, 'razon_contacto_participante_otro');
            $interaccion->contacto_participante_acciones = data_get($data, 'contacto_participante_acciones');
            $interaccion->nueva_fecha_contacto = !empty($data['nueva_fecha_contacto'])
                ? implode('-', array_reverse(explode('/', $data['nueva_fecha_contacto'])))
                : null;
            $interaccion->no_adherencia_pregunta_a = data_get($data, 'no_adherencia_pregunta_a');
            $interaccion->no_adherencia_otro = data_get($data, 'no_adherencia_otro');
            $interaccion->no_adherencia_pregunta_b = data_get($data, 'no_adherencia_pregunta_b');
            $interaccion->no_adherencia_pregunta_c = !empty($data['no_adherencia_pregunta_c'])
                ? implode('-', array_reverse(explode('/', $data['no_adherencia_pregunta_c'])))
                : null;
            $interaccion->problemas_conexion_pregunta_a = data_get($data, 'problemas_conexion_pregunta_a');
            $interaccion->problemas_conexion_otro = data_get($data, 'problemas_conexion_otro');
            $interaccion->problemas_conexion_pregunta_b = data_get($data, 'problemas_conexion_pregunta_b');
            $interaccion->problemas_conexion_pregunta_c = !empty($data['problemas_conexion_pregunta_c'])
                ? implode('-', array_reverse(explode('/', $data['problemas_conexion_pregunta_c'])))
                : null;
            $interaccion->alerta_clinica_pregunta_a = data_get($data, 'alerta_clinica_pregunta_a');
            $interaccion->alerta_clinica_pregunta_b = data_get($data, 'alerta_clinica_pregunta_b');
            $interaccion->alerta_clinica_pregunta_c = data_get($data, 'alerta_clinica_pregunta_c');
            $interaccion->alerta_clinica_pregunta_d = data_get($data, 'alerta_clinica_pregunta_d');
            $interaccion->alerta_clinica_pregunta_e = data_get($data, 'alerta_clinica_pregunta_e');
            $interaccion->alerta_clinica_pregunta_f = data_get($data, 'alerta_clinica_pregunta_f');
            $interaccion->pedido_ayuda_pregunta_a = data_get($data, 'pedido_ayuda_pregunta_a');
            $interaccion->pedido_ayuda_otro = data_get($data, 'pedido_ayuda_otro');
            $interaccion->pedido_ayuda_pregunta_b = data_get($data, 'pedido_ayuda_pregunta_b');
            $interaccion->pedido_ayuda_pregunta_c = data_get($data, 'pedido_ayuda_pregunta_c');
            $interaccion->pedido_ayuda_pregunta_d = !empty($data['pedido_ayuda_pregunta_d'])
                ? implode('-', array_reverse(explode('/', $data['pedido_ayuda_pregunta_d'])))
                : null;
            $interaccion->save();

            if ($alertaId) {
                $dataAlerta = [
                    'revisado' => $interaccion->contactado ? now() : null,
                    'user_id' => auth()->user()->id,
                ];

                if (! $interaccion->contactado) {
                    $dataAlerta['fecha_contacto'] = $interaccion->nueva_fecha_contacto;
                }

                Alerta::where('id', $alertaId)->update($dataAlerta);
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
