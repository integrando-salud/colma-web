<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MultimediaService;
use App\Services\TratamientoService;

class TratamientoController extends Controller
{
    public function getTratamiento(TratamientoService $tratamientoService)
    {
        $tratamiento = $tratamientoService->getTratamientoPaciente(auth()->user()->paciente);

        return response()->json($tratamiento);
    }

    public function getMultimedia(string $path, MultimediaService $multimediaService)
    {
        return $multimediaService->descargarMultimedia($path);
    }
}
