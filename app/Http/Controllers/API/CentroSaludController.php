<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CentroSalud\GetCentroSaludRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Services\CentroSaludService;
use Illuminate\Support\Facades\Log;
use Throwable;

class CentroSaludController extends Controller
{
    public function get(GetCentroSaludRequest $request, CentroSaludService $centroSaludService)
    {
        try {
            $centrosSalud = $centroSaludService->queryGetCentrosSalud($request->validated())->get();

            return CentroSaludResource::collection($centrosSalud);
        } catch (Throwable $th) {
            Log::error('Error al obtener los centros de salud: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al obtener los centros de salud.');
        }
    }
}
