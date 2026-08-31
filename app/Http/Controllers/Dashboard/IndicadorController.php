<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\IndicadorService;
use Inertia\Inertia;

class IndicadorController extends Controller
{
    public function index(IndicadorService $indicadorService)
    {
        return Inertia::render('Indicadores', [
            'indicadoresGenerales' => $indicadorService->getIndicadoresGenerales(auth()->user()),
            'indicadoresEntrenamiento' => $indicadorService->getIndicadoresEntrenamiento(auth()->user()),
            'indicadoresInteraccionesProgramadas' => $indicadorService->getIndicadoresInteraccionesProgramadas(auth()->user()),
            'indicadoresAlertasClinicas' => $indicadorService->getIndicadoresAlertasClinicas(auth()->user()),
            'indicadoresAlertasOperativas' => $indicadorService->getIndicadoresAlertasOperativas(auth()->user()),
            'indicadoresApp' => $indicadorService->getIndicadoresApp(auth()->user()),
        ]);
    }
}
