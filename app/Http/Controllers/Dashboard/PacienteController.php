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
use App\Services\AlertaService;
use App\Services\DashboardService;
use App\Services\PacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class PacienteController extends Controller
{
    private const DETALLE_COMPONENT = 'Pacientes/Detalle';

    public function index(FilterPacienteRequest $request, DashboardService $dashboardService)
    {
        $viewData = $dashboardService->getDataForPacientesPage($request->validated());

        return Inertia::render('Pacientes/Index', $viewData);
    }

    public function create()
    {
        return $this->renderDetalle();
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
            Log::error('Error al guardar paciente: '.$th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al guardar el paciente.');
        }
    }

    public function edit(Paciente $paciente)
    {
        return $this->renderDetalle($paciente);
    }

    public function alertas(Paciente $paciente, AlertaService $alertaService)
    {
        $alertas = $alertaService->getAlertasPaciente($paciente, true);

        return $this->renderDetalle($paciente, 'alertas', [
            'alertasPendientes' => [
                'clinicas' => AlertaResource::collection($alertas['clinicas']),
                'operativas' => AlertaResource::collection($alertas['operativas']),
            ],
        ]);
    }

    public function alertasHistorial(Paciente $paciente, AlertaService $alertaService)
    {
        $alertas = $alertaService->getAlertasPaciente($paciente, false);

        return $this->renderDetalle($paciente, 'alertas-historial', [
            'alertasHistoricas' => [
                'clinicas' => AlertaResource::collection($alertas['clinicas']),
                'operativas' => AlertaResource::collection($alertas['operativas']),
            ],
        ]);
    }

    public function interacciones(Request $request, Paciente $paciente)
    {
        // TODO: agregar una opción para filtrar por rango de fechas de la interacción
        $interacciones = $paciente->interacciones()
            ->whereNull('alerta_id')
            ->orderByDesc('fecha')
            ->paginate(10);

        return $this->renderDetalle($paciente, 'historial-interacciones', [
            'interacciones' => InteraccionResource::collection($interacciones),
            'interaccion' => $request->contacto ?? null,
        ]);
    }

    public function createInteraccion(Request $request, Paciente $paciente, AlertaService $alertaService)
    {
        if (! $paciente->activo) {
            return back()->with('error', 'No se pueden agregar interacciones a un paciente inactivo.');
        }

        $viewData = [];

        if ($request->filled('alerta')) {
            $alerta = Alerta::with(['paciente', 'user'])->find($request->alerta);

            if (! $alerta) {
                return back()->with('error', 'No se encontró la alerta.');
            }

            $alertaService->cargarEntidades(collect([$alerta]));

            $viewData['alerta'] = new AlertaResource($alerta);
        }

        return $this->renderDetalle($paciente, 'nueva-interaccion', $viewData);
    }

    public function editInteraccion(Paciente $paciente, Interaccion $interaccion, AlertaService $alertaService)
    {
        if (auth()->user()->isNotA('superusuario', 'supervisor_pais')) {
            return back()->with('error', 'No tienes permiso para realizar esta acción.');
        }

        if ($interaccion->paciente_id !== $paciente->id) {
            return back()->with('error', 'La interacción no pertenece a este paciente.');
        }

        $interaccion->load(['paciente', 'user', 'alerta.paciente', 'alerta.user']);
        if ($interaccion->alerta) {
            $alertaService->cargarEntidades(collect([$interaccion->alerta]));
        }

        return $this->renderDetalle($paciente, 'nueva-interaccion', [
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

        return $this->renderDetalle($paciente, 'datos-clinicos', [
            'datosClinicos' => JsonResource::collection($registros),
            'filtrosDatosClinicos' => $filtros,
        ]);
    }

    private function renderDetalle(?Paciente $paciente = null, string $tab = 'datos_paciente', array $props = [])
    {
        return Inertia::render(self::DETALLE_COMPONENT, array_merge($props, [
            'paciente' => $paciente ? new PacienteResource($paciente) : null,
            'tab' => $tab,
        ]));
    }
}
