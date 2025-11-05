<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Paciente\FilterPacienteRequest;
use App\Http\Requests\Dashboard\Paciente\StorePacienteRequest;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Models\Paciente;
use App\Services\DashboardService;
use App\Services\PacienteService;
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

    public function create()
    {
        return Inertia::render('Pacientes/Create');
    }

    public function store(StorePacienteRequest $request, PacienteService $pacienteService)
    {
        try {
            $pacienteService->storePaciente($request->validated());

            if ($request->filled('id')) {
                return back()->with('success', 'Paciente actualizado con éxito.');
            } else {
                return redirect()->route('dashboard.pacientes.index')
                    ->with('success', 'Paciente creado con éxito.');
            }
        } catch (Throwable $th) {
            Log::error('Error al guardar paciente: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al guardar el paciente.');
        }
    }

    public function edit(Paciente $paciente)
    {
        return Inertia::render('Pacientes/Create', [
            'paciente' => new PacienteResource($paciente)
        ]);
    }
}
