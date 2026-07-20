<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DatosClinicos\FilterDatosClinicosRequest;
use App\Http\Requests\Dashboard\Paciente\FilterPacienteRequest;
use App\Http\Requests\Dashboard\Paciente\StorePacienteRequest;
use App\Http\Resources\Dashboard\AlertaResource;
use App\Http\Resources\Dashboard\InteraccionResource;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Models\Alerta;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Services\DashboardService;
use App\Services\PacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
                return back()->with('success', 'Se guardó con éxito.');
            } else {
                return redirect()->route('dashboard.pacientes.index')
                    ->with('success', 'Se guardó con éxito.');
            }
        } catch (Throwable $th) {
            Log::error('Error al guardar paciente: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al guardar el paciente.');
        }
    }

    public function edit(Paciente $paciente)
    {
        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente)
        ]);
    }

    public function alertas(Paciente $paciente)
    {
        $alertasClinicas = $paciente->alertas()
            ->whereNull('revisado')
            ->where('tipo', 'clinica')
            ->get();
        $alertasOperativas = $paciente->alertas()
            ->whereNull('revisado')
            ->where('tipo', 'operativa')
            ->get();

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'alertas',
            'alertasPendientes' => [
                'clinicas' => AlertaResource::collection($alertasClinicas),
                'operativas' => AlertaResource::collection($alertasOperativas)
            ]
        ]);
    }

    public function alertasHistorial(Paciente $paciente)
    {
        $alertasClinicas = $paciente->alertas()
            ->whereNotNull('revisado')
            ->where('tipo', 'clinica')
            ->paginate(config('app.rpp'), ['*'], 'page-clinicas');

        $alertasOperativas = $paciente->alertas()
            ->whereNotNull('revisado')
            ->where('tipo', 'operativa')
            ->paginate(config('app.rpp'), ['*'], 'page-operativas');

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
        $interacciones = $paciente->interacciones()
            ->whereNull('alerta_id')
            ->orderByDesc('fecha')
            ->paginate(10);

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'historial-interacciones',
            'interacciones' => InteraccionResource::collection($interacciones),
            'interaccion' => $request->contacto ?? null,
        ]);
    }

    public function createInteraccion(Request $request, Paciente $paciente)
    {
        if (!$paciente->activo) {
            return back()->with('error', 'No se pueden agregar interacciones a un paciente inactivo.');
        }

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

    public function editInteraccion(Paciente $paciente, Interaccion $interaccion)
    {
        if (auth()->user()->isNotA('superusuario', 'supervisor_pais')) {
            return back()->with('error', 'No tienes permiso para realizar esta acción.');
        }

        if ($interaccion->paciente_id !== $paciente->id) {
            return back()->with('error', 'La interacción no pertenece a este paciente.');
        }

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'nueva-interaccion',
            'interaccion' => new InteraccionResource($interaccion),
            'alerta' => $interaccion->alerta ? new AlertaResource($interaccion->alerta) : null,
        ]);
    }

    public function datosClinicos(FilterDatosClinicosRequest $request, Paciente $paciente, PacienteService $pacienteService)
    {
        $filtros = $request->validated();

        $registros = $pacienteService
            ->getDatosClinicos($paciente, 10, $filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->withQueryString();

        return Inertia::render('Pacientes/Detalle', [
            'paciente' => new PacienteResource($paciente),
            'step' => 'datos-clinicos',
            'datosClinicos' => JsonResource::collection($registros),
            'filtrosDatosClinicos' => $filtros,
        ]);
    }
}
