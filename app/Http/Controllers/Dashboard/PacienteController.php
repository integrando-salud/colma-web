<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Paciente\FilterPacienteRequest;
use App\Http\Requests\Dashboard\Paciente\StorePacienteRequest;
use App\Http\Resources\Dashboard\AlertaResource;
use App\Http\Resources\Dashboard\InteraccionResource;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Models\Alerta;
use App\Models\Paciente;
use App\Services\AlertaService;
use App\Services\DashboardService;
use App\Services\PacienteService;
use Illuminate\Http\Request;
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
        return Inertia::render('Pacientes/Detalle');
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
        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente)
        ]);
    }

    public function alertas(Paciente $paciente, AlertaService $alertaService)
    {
        $alertasClinicas = $alertaService->queryGetAlertas([
            'paciente_id' => $paciente->id,
            'tipo' => 'clinica',
            'revisado' => false
        ])->get();

        $alertasOperativas = $alertaService->queryGetAlertas([
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'revisado' => false
        ])->get();

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'alertas',
            'alertasPendientes' => [
                'clinicas' => AlertaResource::collection($alertasClinicas),
                'operativas' => AlertaResource::collection($alertasOperativas)
            ]
        ]);
    }

    public function alertasHistorial(Paciente $paciente, AlertaService $alertaService)
    {
        $alertasClinicas = $alertaService->queryGetAlertas([
            'paciente_id' => $paciente->id,
            'tipo' => 'clinica',
            'revisado' => true
        ])->paginate(config('app.rpp'), ['*'], 'page-clinicas');

        $alertasOperativas = $alertaService->queryGetAlertas([
            'paciente_id' => $paciente->id,
            'tipo' => 'operativa',
            'revisado' => true
        ])->paginate(config('app.rpp'), ['*'], 'page-operativas');

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'alertas-historial',
            'alertasHistoricas' => [
                'clinicas' => AlertaResource::collection($alertasClinicas),
                'operativas' => AlertaResource::collection($alertasOperativas)
            ]
        ]);
    }

    public function interacciones(Request $request, Paciente $paciente)
    {
        // TODO: agregar una opción para filtrar por rango de fechas de la interacción
        $interacciones = $paciente->interacciones()->orderByDesc('fecha')->paginate(10);

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'historial-interacciones',
            'interacciones' => InteraccionResource::collection($interacciones),
            'interaccion' => $request->contacto ?? null,
        ]);
    }

    public function createInteraccion(Request $request, Paciente $paciente, AlertaService $alertaService)
    {
        $viewData = [
            'paciente' => new PacienteResource($paciente),
            'step' => 'nueva-interaccion',
        ];

        if ($request->filled('alerta')) {
            $alerta = Alerta::find($request->alerta);

            if (!$alerta) {
                return back()->with('error', 'No se encontró la alerta.');
            }

            $viewData['alerta'] = new AlertaResource($alerta);
        }

        return Inertia::render('Pacientes/Detalle', $viewData);
    }

    public function datosClinicos(Request $request, Paciente $paciente, PacienteService $pacienteService)
    {
        $registros = $pacienteService->getDatosClinicos($paciente, 10, $request->desde ?? null, $request->hasta ?? null);

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'datos-clinicos',
            'datosClinicos' => $registros
        ]);
    }
}
