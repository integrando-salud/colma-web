<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Diagnostico\GetDiagnosticoRequest;
use App\Http\Resources\Dashboard\DiagnosticoResource;
use App\Services\DiagnosticoService;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiagnosticoController extends Controller
{
    public function get(GetDiagnosticoRequest $request, DiagnosticoService $diagnosticoService)
    {
        try {
            $diagnosticos = $diagnosticoService->queryGetDiagnosticos($request->validated())->get();

            return DiagnosticoResource::collection($diagnosticos);
        } catch (Throwable $th) {
            Log::error('Error al obtener los diagnósticos: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al obtener los diagnósticos.');
        }
    }
}
