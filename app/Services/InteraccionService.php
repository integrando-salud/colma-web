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
                'razon_no_contacto' => data_get($data, 'razon_no_contacto'),
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'modalidad' => $data['modalidad'],
                'motivo' => $data['motivo'],
                'observaciones' => data_get($data, 'observaciones'),
                'accion' => data_get($data, 'accion'),
                'fecha_proximo_contacto' => data_get($data, 'fecha_proximo_contacto'),
                'motivo_reprogramacion' => data_get($data, 'motivo_reprogramacion'),
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
