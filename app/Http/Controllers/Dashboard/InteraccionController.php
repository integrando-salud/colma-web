<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Interacciones\DeleteInteraccionRequest;
use App\Http\Requests\Dashboard\Interacciones\StoreInteraccionRequest;
use App\Models\Interaccion;
use App\Services\InteraccionService;
use Illuminate\Support\Facades\Log;
use Throwable;

class InteraccionController extends Controller
{
    public function getRazonesNoContacto()
    {
        return Interaccion::RAZONES_NO_CONTACTO;
    }

    public function getModalidades()
    {
        return Interaccion::MODALIDADES;
    }

    public function getAcciones()
    {
        return Interaccion::ACCIONES;
    }

    public function getMotivosContacto()
    {
        return Interaccion::MOTIVOS_CONTACTO;
    }

    public function getMotivosReprogramacion()
    {
        return Interaccion::MOTIVOS_REPROGRAMACION;
    }

    public function store(StoreInteraccionRequest $request, InteraccionService $interaccionService)
    {
        try {
            $interaccionService->storeInteraccion($request->validated());

            return to_route('dashboard.pacientes.interacciones', [
                'paciente' => $request->paciente_id,
            ])->with('success', 'Se guardó con éxito.');
        } catch (Throwable $th) {
            Log::error('Error al guardar interacción: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al guardar la interacción.');
        }
    }

    public function delete(DeleteInteraccionRequest $request, InteraccionService $interaccionService)
    {
        try {
            $interaccion = Interaccion::find($request->interaccion);
            $pacienteId = $interaccion->paciente_id;
            $interaccionService->deleteInteraccion($interaccion);

            return to_route('dashboard.pacientes.interacciones', [
                'paciente' => $pacienteId,
            ])->with('success', 'Se eliminó con éxito.');
        } catch (Throwable $th) {
            Log::error('Error al eliminar interacción: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al eliminar la interacción.');
        }
    }
}
