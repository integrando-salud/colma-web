<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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

            return to_route(route('dashboard.pacientes.interacciones'), $request->paciente_id);
        } catch (Throwable $th) {
            Log::error('Error al guardar interacción: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al guardar la interacción.');
        }
    }
}
