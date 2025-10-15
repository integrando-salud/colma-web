<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\CentroSaludService;
use App\Services\DiagnosticoService;
use App\Services\UsuarioService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PacienteController extends Controller
{
    public function index()
    {
        return Inertia::render('Pacientes/Index', []);
    }

    public function create(CentroSaludService $centroSaludService, UsuarioService $usuarioService, DiagnosticoService $diagnosticoService)
    {
        $centrosSalud = $centroSaludService->queryGetCentrosSalud([
            'active' => true,
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $promotores = $usuarioService->queryGetUsuarios([
            'rol' => 'promotor',
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $diagnosticos = $diagnosticoService->queryGetDiagnosticos([
            'active' => true,
        ])->get();

        $paquetes = [];

        return Inertia::render('Pacientes/Create', compact(
            'centrosSalud',
            'promotores',
            'diagnosticos',
            'paquetes',
        ));
    }
}
