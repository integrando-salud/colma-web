<?php

namespace App\Services;

use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\PacienteListResource;
use App\Http\Resources\Dashboard\PaisResource;
use App\Http\Resources\Dashboard\RolResource;
use App\Http\Resources\Dashboard\SupervisorResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use UnexpectedValueException;

class DashboardService
{
    /**
     * Resuelve la vista inicial del panel y carga los datos permitidos para el
     * rol principal del usuario autenticado.
     *
     * Se aceptan únicamente los filtros que cada listado puede procesar para
     * evitar que parámetros ajenos se propaguen a las consultas.
     *
     * @return array{name: string, data: array<string, mixed>}
     *
     * @throws UnexpectedValueException Si el usuario no tiene un rol de panel compatible.
     */
    public function handleIndexView(Request $request): array
    {
        $rolName = auth()->user()->roles->first()?->name;

        switch ($rolName) {
            case 'superusuario':
            case 'supervisor':
            case 'supervisor_pais':
                $viewName = 'Index/UsuariosIndex';
                $viewData = $this->getDataForUsuariosPage($request->only(['rol', 'pais', 'nombre', 'orderBy', 'orderDir']));
                break;
            case 'promotor':
                $viewName = 'Index/PacientesIndex';
                $viewData = $this->getDataForPacientesPage($request->only([
                    'estado',
                    'alertas',
                    'nombre',
                    'orderBy',
                    'orderDir',
                ]));
                break;
            default:
                throw new UnexpectedValueException('El usuario autenticado no tiene un rol compatible con el panel.');
        }

        return [
            'name' => $viewName,
            'data' => $viewData,
        ];
    }

    /**
     * Obtiene los datos necesarios para el listado y los formularios de
     * usuarios, aplicando el alcance territorial del usuario autenticado.
     *
     * Además de paginar los usuarios, carga los catálogos requeridos por la
     * interfaz y reutiliza los países ya obtenidos para evitar consultas N+1
     * al serializar usuarios, supervisores y centros de salud.
     *
     * @param  array{rol?: string, pais?: int|string, nombre?: string, orderBy?: string, orderDir?: string}  $filters
     * @return array<string, mixed>
     */
    public function getDataForUsuariosPage(array $filters = []): array
    {
        $usuarioService = new UsuarioService;
        $rolService = new RolService;
        $paisService = new PaisService;
        $centroSaludService = new CentroSaludService;
        $authUser = auth()->user();

        if (empty($filters['orderBy'])) {
            $filters['orderBy'] = 'id';
        }

        if (empty($filters['orderDir'])) {
            $filters['orderDir'] = 'desc';
        }

        if ($authUser->isA('supervisor') || $authUser->isA('supervisor_pais')) {
            $filters['pais'] = $authUser->pais_id;
        }

        $usuarios = $usuarioService->queryGetUsuarios([...$filters, 'withoutPais' => true])
            ->paginate(config('app.rpp'))
            ->withQueryString();

        $usuarioAutenticadoEnPagina = $usuarios->getCollection()->first(
            fn (User $usuario): bool => $usuario->is($authUser)
        );
        if ($usuarioAutenticadoEnPagina instanceof User && $usuarioAutenticadoEnPagina->relationLoaded('roles')) {
            $authUser->setRelation('roles', $usuarioAutenticadoEnPagina->roles);
        }

        $rolesExcluidos = ['paciente'];
        if ($authUser->isA('supervisor')) {
            $rolesExcluidos = array_merge($rolesExcluidos, [
                'superusuario',
                'supervisor',
                'supervisor_pais',
            ]);
        } elseif ($authUser->isA('supervisor_pais')) {
            $rolesExcluidos = array_merge($rolesExcluidos, [
                'superusuario',
                'supervisor_pais',
            ]);
        }

        $roles = $rolService->queryGetRoles([
            'excepto' => $rolesExcluidos,
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $paises = $paisService->queryGetPaises([
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        $centrosSalud = $centroSaludService->queryGetCentrosSalud([
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])
            ->when(
                $authUser->isA('supervisor') || $authUser->isA('supervisor_pais'),
                fn ($query) => $query->where('pais_id', $authUser->pais_id)
            )
            ->get();

        $supervisores = $authUser->isA('supervisor')
            ? collect([$authUser->loadMissing('centroSalud')])
            : $usuarioService->queryGetUsuarios([
                'rol' => 'supervisor',
                'orderBy' => 'nombre',
                'orderDir' => 'asc',
                'forSelector' => true,
                'withoutPais' => true,
            ])->get();

        $paisesPorId = $paises->keyBy('id');
        $this->attachPaises($usuarios->getCollection(), $paisesPorId);
        $this->attachPaises($supervisores, $paisesPorId);
        $this->attachPaises($centrosSalud, $paisesPorId);
        $this->attachPaises(collect([$authUser]), $paisesPorId);

        $paisesDisponibles = $paises
            ->where('activo', true)
            ->when(
                $authUser->isA('supervisor') || $authUser->isA('supervisor_pais'),
                fn (Collection $items) => $items->where('id', $authUser->pais_id)
            );

        return [
            'usuarios' => UserResource::collection($usuarios),
            'roles' => RolResource::collection($roles),
            'paises' => PaisResource::collection($paisesDisponibles),
            'centrosSalud' => CentroSaludResource::collection($centrosSalud),
            'supervisores' => SupervisorResource::collection($supervisores),
            'filtros' => $filters,
        ];
    }

    /**
     * Asigna la relación de país desde una colección indexada en los modelos
     * ya cargados, incluyendo el centro de salud cuando esté precargado.
     *
     * Esto evita que los resources disparen una consulta por cada relación de
     * país durante la serialización.
     *
     * @param  Collection<int, mixed>  $models
     * @param  Collection<int, mixed>  $paisesPorId
     */
    private function attachPaises(Collection $models, Collection $paisesPorId): void
    {
        $models->each(function ($model) use ($paisesPorId) {
            $model->setRelation('pais', $paisesPorId->get($model->pais_id));

            if ($model->relationLoaded('centroSalud') && $model->centroSalud) {
                $model->centroSalud->setRelation(
                    'pais',
                    $paisesPorId->get($model->centroSalud->pais_id)
                );
            }
        });
    }

    /**
     * Obtiene el listado paginado de pacientes con las relaciones necesarias
     * para serializarlo sin consultas adicionales por cada paciente.
     *
     * El servicio de pacientes aplica el alcance del usuario autenticado; este
     * método solo completa la preparación específica de la pantalla, como los
     * contactos próximos y el selector de promotores del supervisor.
     *
     * @param  array{estado?: bool|int|string, alertas?: string, diagnostico?: int|string, nombre?: string, orderBy?: string, orderDir?: string, promotor?: int|string}  $filters
     * @return array<string, mixed>
     */
    public function getDataForPacientesPage(array $filters = []): array
    {
        $pacienteService = new PacienteService;

        if (empty($filters['orderBy'])) {
            $filters['orderBy'] = 'id';
        }

        if (empty($filters['orderDir'])) {
            $filters['orderDir'] = 'desc';
        }

        $pacientes = $pacienteService->queryGetPacientes($filters)
            ->with([
                'centroSalud',
                'promotor',
                'usuario',
                'diagnosticos',
                'entrenamientos' => fn ($query) => $query
                    ->where('activo', true)
                    ->latest(),
                'proximosContactosProgramados' => fn ($query) => $query
                    ->whereNotNull('nueva_fecha_contacto')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('interacciones AS subquery')
                            ->whereColumn('interacciones.paciente_id', 'subquery.paciente_id')
                            ->where('subquery.contactado', true)
                            ->whereColumn('subquery.fecha', '>=', 'interacciones.nueva_fecha_contacto')
                            ->whereNull('subquery.deleted_at');
                    })
                    ->orderBy('nueva_fecha_contacto')
                    ->limit(3)
                    ->selectRaw('id, paciente_id, motivo, nueva_fecha_contacto AS fecha'),
                'proximosContactosAlertas' => fn ($query) => $query
                    ->whereNotNull('fecha_contacto')
                    ->whereNull('revisado')
                    ->orderBy('fecha_contacto')
                    ->limit(3)
                    ->selectRaw("id, paciente_id, fecha_contacto AS fecha, CASE
                        WHEN tipo = 'operativa' THEN 'Alerta operativa'
                        WHEN tipo = 'clinica' THEN 'Alerta clínica'
                        ELSE ''
                    END AS motivo"),
            ])
            ->paginate(config('app.rpp'))
            ->withQueryString();

        return [
            'pacientes' => PacienteListResource::collection($pacientes),
            'promotores' => auth()->user()->isA('supervisor')
                ? UserResource::collection(auth()->user()->promotores()->orderBy('nombre')->get())
                : null,
            'filtros' => $filters,
        ];
    }
}
