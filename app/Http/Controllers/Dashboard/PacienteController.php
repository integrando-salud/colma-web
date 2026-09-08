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
use App\Models\User;
use App\Services\AlertaService;
use App\Services\DashboardService;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PacienteController extends Controller
{
    private const string DETALLE_COMPONENT = 'Pacientes/Detalle';

    /**
     * Muestra el listado de pacientes dentro del alcance del usuario autenticado.
     *
     * Los filtros y el ordenamiento ya fueron validados por FilterPacienteRequest;
     * DashboardService prepara los datos auxiliares que consume la vista.
     */
    public function index(FilterPacienteRequest $request, DashboardService $dashboardService): Response
    {
        $viewData = $dashboardService->getDataForPacientesPage($request->validated());

        return Inertia::render('Pacientes/Index', $viewData);
    }

    /**
     * Muestra el detalle vacío utilizado para crear un paciente.
     */
    public function create(): Response
    {
        return $this->renderDetalle();
    }

    /**
     * Crea o actualiza un paciente a partir de los datos validados del formulario.
     *
     * StorePacienteRequest decide si se trata de un alta o una edición y autoriza
     * ambas operaciones. El servicio persiste el paciente y sus relaciones de forma
     * transaccional.
     */
    public function store(StorePacienteRequest $request, PacienteService $pacienteService): RedirectResponse
    {
        try {
            $pacienteService->storePaciente($request->validated());

            if ($request->filled('id')) {
                return back()->with('success', 'Se guardó con éxito.');
            } else {
                return redirect()->route('dashboard.pacientes.index')
                    ->with('success', 'Se guardó con éxito.');
            }
        } catch (Throwable $exception) {
            Log::error('Error al guardar paciente.', [
                'exception' => $exception,
            ]);

            return back()->with('error', 'Error al guardar el paciente.');
        }
    }

    /**
     * Muestra el detalle de un paciente para su edición.
     */
    public function edit(Paciente $paciente): Response
    {
        return $this->renderDetalle($paciente);
    }

    /**
     * Muestra las alertas pendientes del paciente, separadas por tipo.
     */
    public function alertas(Paciente $paciente, AlertaService $alertaService): Response
    {
        $alertas = $alertaService->getAlertasPaciente($paciente, true);

        return $this->renderDetalle($paciente, 'alertas', [
            'alertasPendientes' => [
                'clinicas' => AlertaResource::collection($alertas['clinicas']),
                'operativas' => AlertaResource::collection($alertas['operativas']),
            ],
        ]);
    }

    /**
     * Muestra el historial de alertas ya revisadas del paciente, separado por tipo.
     */
    public function alertasHistorial(Paciente $paciente, AlertaService $alertaService): Response
    {
        $alertas = $alertaService->getAlertasPaciente($paciente, false);

        return $this->renderDetalle($paciente, 'alertas-historial', [
            'alertasHistoricas' => [
                'clinicas' => AlertaResource::collection($alertas['clinicas']),
                'operativas' => AlertaResource::collection($alertas['operativas']),
            ],
        ]);
    }

    /**
     * Muestra todas las interacciones del paciente, ordenadas desde la más reciente.
     *
     * Las relaciones requeridas por InteraccionResource se cargan por adelantado para
     * evitar consultas adicionales por cada elemento de la página.
     */
    public function interacciones(Request $request, Paciente $paciente): Response
    {
        $origenInteraccion = $request->query('origen') === 'alertas' ? 'alertas' : 'contactos';
        $interacciones = $paciente->interacciones()
            ->with(['paciente', 'user', 'alerta'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate(10);

        $interaccionSeleccionada = null;
        if ($request->filled('contacto')) {
            $interaccionSeleccionada = Interaccion::query()
                ->with(['paciente', 'user', 'alerta.paciente', 'alerta.user'])
                ->whereKey($request->integer('contacto'))
                ->where('paciente_id', $paciente->id)
                ->first();

            if ($interaccionSeleccionada?->alerta) {
                $alertaService = app(AlertaService::class);
                $alertaService->cargarEntidades(collect([$interaccionSeleccionada->alerta]));
            }
        }

        return $this->renderDetalle($paciente, 'historial-interacciones', [
            'interacciones' => InteraccionResource::collection($interacciones),
            'interaccion' => $interaccionSeleccionada ? new InteraccionResource($interaccionSeleccionada) : null,
            'origenInteraccion' => $origenInteraccion,
        ]);
    }

    /**
     * Muestra el formulario para registrar una interacción de un paciente activo.
     *
     * Cuando se recibe el parámetro opcional `alerta`, solamente se carga si pertenece
     * al paciente de la ruta; así se evita asociar o exponer alertas de otro paciente.
     */
    public function createInteraccion(Request $request, Paciente $paciente, AlertaService $alertaService): Response|RedirectResponse
    {
        if (! $paciente->activo) {
            return back()->with('error', 'No se pueden agregar interacciones a un paciente inactivo.');
        }

        $viewData = [];

        if ($request->filled('alerta')) {
            $alerta = Alerta::query()
                ->with(['paciente', 'user'])
                ->whereKey($request->integer('alerta'))
                ->where('paciente_id', $paciente->id)
                ->first();

            if (! $alerta) {
                return back()->with('error', 'No se encontró una alerta para este paciente.');
            }

            $alertaService->cargarEntidades(collect([$alerta]));

            $viewData['alerta'] = new AlertaResource($alerta);
        }

        return $this->renderDetalle($paciente, 'nueva-interaccion', $viewData);
    }

    /**
     * Muestra una interacción existente para su edición.
     *
     * Solo los superusuarios y supervisores de país pueden editar. También se valida
     * que el binding de interacción pertenezca al paciente de la ruta.
     */
    public function editInteraccion(Paciente $paciente, Interaccion $interaccion, AlertaService $alertaService): Response|RedirectResponse
    {
        if (auth()->user()->isNotA(User::ROL_SUPERUSUARIO, User::ROL_SUPERVISOR_PAIS)) {
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

    /**
     * Muestra las mediciones clínicas combinadas del paciente y aplica el rango de
     * fechas opcional validado por FilterDatosClinicosRequest.
     */
    public function datosClinicos(FilterDatosClinicosRequest $request, Paciente $paciente, PacienteService $pacienteService): Response
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

    /**
     * Renderiza la pantalla de detalle y completa las relaciones requeridas por el
     * recurso del paciente cuando se está consultando uno existente.
     *
     * @param  array<string, mixed>  $props  Propiedades adicionales específicas de la pestaña.
     */
    private function renderDetalle(?Paciente $paciente = null, string $tab = 'datos_paciente', array $props = []): Response
    {
        if ($paciente) {
            app(PacienteService::class)->cargarRelacionesParaResource($paciente);
        }

        return Inertia::render(self::DETALLE_COMPONENT, array_merge($props, [
            'paciente' => $paciente ? new PacienteResource($paciente) : null,
            'tab' => $tab,
        ]));
    }
}
