<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Paquete\GetPaqueteRequest;
use App\Http\Resources\Dashboard\PaqueteResource;
use App\Services\PaqueteService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaqueteController extends Controller
{
    public function get(GetPaqueteRequest $request, PaqueteService $paqueteService)
    {
        try {
            $paquetes = $paqueteService->queryGetPaquetes($request->validated())->get();

            return PaqueteResource::collection($paquetes);
        } catch (Throwable $th) {
            Log::error('Error al obtener los paquetes: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al obtener los paquetes.');
        }
    }
}
