<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Paciente\SyncDataRequest;
use App\Services\DatosClinicosService;
use Throwable;

class PacienteController extends Controller
{
    public function syncData(SyncDataRequest $request, DatosClinicosService $datosClinicosService)
    {
        try {
            $datosClinicosService->syncDatosApp($request->validated());

            return response()->json([
                'message' => 'Datos sincronizados con éxito.',
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'Error al sincronizar los datos.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
