<?php

namespace App\Services;

use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\PacienteListResource;
use App\Http\Resources\Dashboard\PaisResource;
use App\Http\Resources\Dashboard\RolResource;
use App\Http\Resources\Dashboard\SupervisorResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardService
{
    public function handleIndexView(Request $request)
    {
        $rolName = auth()->user()->roles->first()->name;

        switch ($rolName) {
            case 'superusuario':
            case 'supervisor':
            case 'supervisor_pais':
                $viewName = 'Index/UsuariosIndex';
                $viewData = $this->getDataForUsuariosPage($request->only(['rol', 'pais', 'nombre', 'orderBy', 'orderDir']));
                break;
            case 'promotor':
                $viewName = 'Index/PacientesIndex';
                $viewData = $this->getDataForPacientesPage($request->only('estado', 'alertas', 'nombre', 'orderBy', 'orderDir'));
                break;
        }

        return [
            'name' => $viewName,
            'data' => $viewData,
        ];
    }

    public function getDataForUsuariosPage(array $filters = [])
    {
        $usuarioService = new UsuarioService();
        $rolService = new RolService();
        $paisService = new PaisService();
        $centroSaludService = new CentroSaludService();
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

        $usuarioAutenticadoEnPagina = $usuarios->getCollection()->firstWhere('id', $authUser->id);
        if ($usuarioAutenticadoEnPagina?->relationLoaded('roles')) {
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
        ])->get();

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

    public function getDataForPacientesPage(array $filters = [])
    {
        $pacienteService = new PacienteService();

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
                            ->whereRaw('interacciones.nueva_fecha_contacto < subquery.fecha')
                            ->whereColumn('interacciones.paciente_id', 'subquery.paciente_id');
                    })
                    ->orderBy('nueva_fecha_contacto')
                    ->selectRaw('id, paciente_id, motivo, nueva_fecha_contacto AS fecha'),
                'proximosContactosAlertas' => fn ($query) => $query
                    ->whereNotNull('fecha_contacto')
                    ->whereNull('revisado')
                    ->orderBy('fecha_contacto')
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
