<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Paciente\FilterPacienteRequest;
use App\Http\Requests\Dashboard\Paciente\StorePacienteRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\DiagnosticoResource;
use App\Http\Resources\Dashboard\PaqueteResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Services\CentroSaludService;
use App\Services\DashboardService;
use App\Services\DiagnosticoService;
use App\Services\PacienteService;
use App\Services\PaqueteService;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class PacienteController extends Controller
{
    public function index(FilterPacienteRequest $request, DashboardService $dashboardService)
    {
        $viewData = $dashboardService->getDataForPacientesPage($request->validated());

        return Inertia::render('Pacientes/Index', $viewData);
    }

    public function create(
        CentroSaludService $centroSaludService,
        UsuarioService $usuarioService,
        DiagnosticoService $diagnosticoService,
        PaqueteService $paqueteService
    )
    {
        $centrosSalud = $centroSaludService->queryGetCentrosSalud([
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        $promotores = $usuarioService->queryGetUsuarios([
            'rol' => 'promotor',
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        $diagnosticos = $diagnosticoService->queryGetDiagnosticos([
            'activo' => true,
        ])->get();

        $paquetes = $paqueteService->queryGetPaquetes([
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        return Inertia::render('Pacientes/Create', [
            'centrosSalud' => CentroSaludResource::collection($centrosSalud),
            'promotores' => UserResource::collection($promotores),
            'diagnosticos' => DiagnosticoResource::collection($diagnosticos),
            'paquetes' => PaqueteResource::collection($paquetes),
        ]);
    }

    public function store(StorePacienteRequest $request, PacienteService $pacienteService)
    {
        try {
            $pacienteService->storePaciente($request->validated());

            return redirect()->route('dashboard.pacientes.index')
                ->with('success', 'Paciente ' . ($request->filled('id') ? 'actualizado' : 'creado') . ' con éxito.');
        } catch (Throwable $th) {
            Log::error('Error al guardar paciente: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al guardar el paciente.');
        }
    }
}
