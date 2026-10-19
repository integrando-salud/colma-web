<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UsuarioService
{
    /**
     * Campos expuestos por la grilla de usuarios que admiten ordenamiento.
     *
     * La lista evita interpolar nombres de columna arbitrarios en la consulta.
     */
    private const array ORDERABLE_COLUMNS = [
        'id', 'nombre', 'email', 'documento', 'tipo', 'pais', 'centro_salud',
        'supervisor', 'pacientes', 'alertas',
    ];

    /**
     * Construye la consulta del listado de usuarios según los permisos y filtros recibidos.
     *
     * La consulta no se ejecuta para permitir que el consumidor la pagine o agregue condiciones.
     * Los supervisores solo acceden a los promotores de su país y los supervisores de país solo
     * a promotores y supervisores de su país.
     *
     * @param  array{forSelector?: bool, onlyPromotores?: bool, withoutPais?: bool, rol?: string, pais?: int|string, nombre?: string, orderBy?: string, orderDir?: string}  $filters
     * @return Builder<User>
     */
    public function queryGetUsuarios(array $filters = []): Builder
    {
        $authUser = auth()->user();
        $forSelector = (bool) data_get($filters, 'forSelector', false);
        $onlyPromotores = (bool) data_get($filters, 'onlyPromotores', false);
        $withoutPais = (bool) data_get($filters, 'withoutPais', false);
        $relations = $forSelector
            ? ['pais', 'centroSalud']
            : ['pais', 'centroSalud', 'roles', 'supervisor'];

        if ($withoutPais) {
            $relations = array_values(array_diff($relations, ['pais']));
        }

        $query = User::query()
            ->select('users.*')
            ->with($relations)
            ->whereIsNot('paciente');

        if (!$forSelector) {
            $this->addDashboardCounts($query);
        }

        if ($authUser->isA(User::ROL_PROMOTOR)) {
            // Un promotor no administra usuarios ni puede enumerar a sus pares.
            $query->whereKey($authUser->getKey());
        } elseif ($authUser->isA(User::ROL_SUPERVISOR)) {
            $query->where('supervisor_id', $authUser->id)
                ->where('pais_id', $authUser->pais_id);
        } elseif ($authUser->isA(User::ROL_SUPERVISOR_PAIS)) {
            $query->where('pais_id', $authUser->pais_id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    User::ROL_SUPERVISOR,
                    User::ROL_PROMOTOR,
                ]);
            });
        } elseif ($authUser->isNotA(User::ROL_SUPERUSUARIO)) {
            // Pacientes y roles no contemplados no pueden descubrir usuarios.
            $query->whereRaw('1 = 0');
        }

        if ($onlyPromotores) {
            $query->whereIs(User::ROL_PROMOTOR);
        } elseif (!empty($filters['rol'])) {
            $query->whereHas('roles', function ($query) use ($filters) {
                $query->where('name', $filters['rol']);
            });
        }

        if (!empty($filters['pais']) && !$authUser->isA(User::ROL_SUPERVISOR) && !$authUser->isA(User::ROL_SUPERVISOR_PAIS)) {
            $query->where('pais_id', $filters['pais']);
        }

        if (!empty($filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $filters['nombre'] . '%');
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderBy = in_array($orderBy, self::ORDERABLE_COLUMNS, true) ? $orderBy : 'id';
        $orderDir = data_get($filters, 'orderDir', 'desc');
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        switch ($orderBy) {
            case 'tipo':
                $query->leftJoin('assigned_roles', function ($join) {
                        $join->on('users.id', '=', 'assigned_roles.entity_id')
                            ->where('assigned_roles.entity_type', '=', User::class);
                    })
                    ->leftJoin('roles', 'roles.id', '=', 'assigned_roles.role_id')
                    ->orderBy('roles.title', $orderDir);
                break;
            case 'pais':
                $query->leftJoin('paises', 'paises.id', '=', 'users.pais_id')
                    ->orderBy('paises.nombre', $orderDir);
                break;
            case 'centro_salud':
                $query->leftJoin('centros_salud', 'centros_salud.id', '=', 'users.centro_salud_id')
                    ->orderBy('centros_salud.nombre', $orderDir);
                break;
            case 'supervisor':
                $query->leftJoin('users as supervisors', 'supervisors.id', '=', 'users.supervisor_id')
                    ->orderBy('supervisors.nombre', $orderDir);
                break;
            case 'pacientes':
                $query->orderBy('pacientes_count', $orderDir);
                break;
            case 'alertas':
                $query->orderBy('alertas_count', $orderDir);
                break;
            default:
                $query->orderBy($orderBy, $orderDir);
        }

        return $query;
    }

    /**
     * Añade a la consulta los contadores de pacientes y alertas pendientes visibles por rol.
     *
     * Se calculan como subconsultas SQL para evitar cargar pacientes y alertas de cada usuario.
     *
     * @param  Builder<User>  $query
     */
    private function addDashboardCounts(Builder $query): void
    {
        $roles = [User::class, User::class, User::class, User::class];

        $query->selectRaw("CASE
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'superusuario') THEN (SELECT COUNT(*) FROM pacientes p WHERE p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0)
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'supervisor_pais') THEN (SELECT COUNT(*) FROM pacientes p INNER JOIN centros_salud cs ON cs.id = p.centro_salud_id WHERE p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND cs.pais_id = users.pais_id)
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'supervisor') THEN (SELECT COUNT(*) FROM pacientes p WHERE p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND p.promotor_id IN (SELECT up.id FROM users up WHERE up.supervisor_id = users.id AND up.deleted_at IS NULL))
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'promotor') THEN (SELECT COUNT(*) FROM pacientes p WHERE p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND p.promotor_id = users.id)
            ELSE 0 END AS pacientes_count", $roles)
            ->selectRaw("CASE
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'superusuario') THEN (SELECT COUNT(*) FROM alertas a INNER JOIN pacientes p ON p.id = a.paciente_id WHERE a.revisado IS NULL AND a.deleted_at IS NULL AND p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0)
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'supervisor_pais') THEN (SELECT COUNT(*) FROM alertas a INNER JOIN pacientes p ON p.id = a.paciente_id INNER JOIN centros_salud cs ON cs.id = p.centro_salud_id WHERE a.revisado IS NULL AND a.deleted_at IS NULL AND p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND cs.pais_id = users.pais_id)
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'supervisor') THEN (SELECT COUNT(*) FROM alertas a INNER JOIN pacientes p ON p.id = a.paciente_id WHERE a.revisado IS NULL AND a.deleted_at IS NULL AND p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND p.promotor_id IN (SELECT up.id FROM users up WHERE up.supervisor_id = users.id AND up.deleted_at IS NULL))
            WHEN EXISTS (SELECT 1 FROM assigned_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.entity_id = users.id AND ar.entity_type = ? AND r.name = 'promotor') THEN (SELECT COUNT(*) FROM alertas a INNER JOIN pacientes p ON p.id = a.paciente_id WHERE a.revisado IS NULL AND a.deleted_at IS NULL AND p.deleted_at IS NULL AND p.oculto = 0 AND p.test = 0 AND p.promotor_id = users.id)
            ELSE 0 END AS alertas_count", $roles);
    }

    /**
     * Crea o actualiza un usuario y sincroniza su único rol de panel.
     *
     * Al crear o recibir una contraseña no vacía, el cast `hashed` del modelo la almacena cifrada.
     * Para promotores, el país se toma siempre del supervisor seleccionado.
     *
     * @param  array{id?: int, nombre: string, sexo: string, documento: string, email: string, password?: string, pais_id: int, centro_salud_id?: int|null, supervisor_id?: int|null, rol: string}  $data
     */
    public function storeUsuario(array $data): User
    {
        $user = empty($data['id']) ? new User() : User::findOrFail($data['id']);
        $supervisor = ($data['rol'] === 'promotor' && !empty($data['supervisor_id']))
            ? User::find($data['supervisor_id'])
            : null;

        $user->nombre = $data['nombre'];
        $user->sexo = $data['sexo'];
        $user->documento = $data['documento'];
        $user->email = $data['email'];
        $user->pais_id = $supervisor?->pais_id ?? $data['pais_id'];
        $user->centro_salud_id = $data['centro_salud_id'] ?? null;
        $user->supervisor_id = $data['rol'] === User::ROL_PROMOTOR
            ? ($data['supervisor_id'] ?? null)
            : null;

        if (empty($data['id']) || !empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        Bouncer::sync($user)->roles([$data['rol']]);
        Bouncer::refreshFor($user);

        return $user;
    }

    /**
     * Elimina lógicamente un usuario.
     */
    public function deleteUsuario(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Restaura un usuario eliminado lógicamente.
     */
    public function reactivateUsuario(User $user): bool
    {
        return $user->restore();
    }

    /**
     * Actualiza la contraseña de un usuario.
     *
     * El cast `hashed` del modelo garantiza que nunca se persista en texto plano.
     */
    public function updatePassword(User $user, string $password): bool
    {
        $user->password = $password;
        return $user->save();
    }

    /**
     * Cuenta los pacientes visibles para un usuario, incluidos los inactivos.
     *
     * Devuelve cero para IDs inexistentes o roles sin alcance sobre pacientes.
     *
     * @param int $userId
     * @return int
     */
    public function countPacientes(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        return $user ? $this->queryPacientesByUsuario($user)->count() : 0;
    }

    /**
     * Cuenta únicamente los pacientes activos visibles para un usuario.
     *
     * Devuelve cero para IDs inexistentes o roles sin alcance sobre pacientes.
     *
     * @param int $userId
     * @return int
     */
    public function countPacientesActivos(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        return $user ? $this->queryPacientesActivosByUsuario($user)?->count() ?? 0 : 0;
    }

    /**
     * Cuenta pacientes activos con al menos una alerta pendiente en el alcance del usuario.
     *
     * Cada paciente se cuenta una sola vez, aunque tenga varias alertas pendientes.
     *
     * @param int $userId
     * @return int
     */
    public function countPacientesConAlertas(int $userId): int
    {
        $user = User::withTrashed()->find($userId);
        $query = $user ? $this->queryPacientesActivosByUsuario($user) : null;

        return $query
            ? $query->whereHas('alertas', fn (Builder $query) => $query->whereNull('revisado'))->count()
            : 0;
    }

    /**
     * Cuenta las alertas pendientes de los pacientes visibles para un usuario.
     *
     * A diferencia de {@see countPacientesConAlertas()}, aquí se contabiliza cada alerta.
     *
     * @param int $userId
     * @return int
     */
    public function countAlertas(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        return $user ? $this->countAlertasPacientes($user) : 0;
    }

    /**
     * Cuenta pacientes activos con alertas pendientes o contactos programados aún no realizados.
     */
    public function countPacientesConContactosPendientes(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        if (!$user) {
            return 0;
        }

        $query = $this->queryPacientesActivosByUsuario($user);

        if (!$query) {
            return 0;
        }

        return $this->applyFiltroContactosPendientes($query)->count();
    }

    /**
     * Construye la consulta de pacientes activos que un usuario puede gestionar.
     *
     * @return Builder<Paciente>|null Null cuando el rol no tiene alcance sobre pacientes.
     */
    private function queryPacientesActivosByUsuario(User $user): ?Builder
    {
        if ($user->isA('promotor')) {
            return Paciente::query()->paraEstadisticas()
                ->where('activo', true)
                ->where('promotor_id', $user->id);
        }

        if ($user->isA('supervisor')) {
            return Paciente::query()->paraEstadisticas()
                ->where('activo', true)
                ->whereIn('promotor_id', $user->promotores->pluck('id'));
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::query()->paraEstadisticas()
                ->where('activo', true)
                ->whereHas('centroSalud', function ($query) use ($user) {
                    $query->where('pais_id', $user->pais_id);
                });
        }

        if ($user->isA('superusuario')) {
            return Paciente::query()->paraEstadisticas()->where('activo', true);
        }

        return null;
    }

    /**
     * Construye la consulta de pacientes visibles para un usuario, activos o no.
     *
     * @return Builder<Paciente>
     */
    private function queryPacientesByUsuario(User $user): Builder
    {
        if ($user->isA('promotor')) {
            return Paciente::query()->paraEstadisticas()->where('promotor_id', $user->id);
        }

        if ($user->isA('supervisor')) {
            return Paciente::query()->paraEstadisticas()->whereIn('promotor_id', $user->promotores()->select('id'));
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::query()->paraEstadisticas()->whereHas('centroSalud', fn (Builder $query) => $query->where('pais_id', $user->pais_id));
        }

        return $user->isA('superusuario') ? Paciente::query()->paraEstadisticas() : Paciente::query()->paraEstadisticas()->whereRaw('1 = 0');
    }

    /**
     * Restringe una consulta a pacientes que requieren contacto.
     *
     * Se considera pendiente una alerta no revisada o una fecha programada sin contacto posterior.
     *
     * @param  Builder<Paciente>  $query
     * @return Builder<Paciente>
     */
    private function applyFiltroContactosPendientes(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereHas('alertas', function (Builder $query) {
                $query->whereNull('revisado');
            })->orWhere(function (Builder $query) {
                $query->whereExists(function ($subquery) {
                    $subquery->selectRaw(1)
                        ->from('interacciones as i_programada')
                        ->whereColumn('i_programada.paciente_id', 'pacientes.id')
                        ->whereNotNull('i_programada.nueva_fecha_contacto')
                        ->whereNull('i_programada.deleted_at');
                })->whereNotExists(function ($subquery) {
                    $subquery->selectRaw(1)
                        ->from('interacciones as i_contactado')
                        ->whereColumn('i_contactado.paciente_id', 'pacientes.id')
                        ->where('i_contactado.contactado', true)
                        ->whereNull('i_contactado.deleted_at')
                        ->whereRaw("
                            i_contactado.fecha >= (
                                SELECT MIN(i_programada_min.nueva_fecha_contacto)
                                FROM interacciones AS i_programada_min
                                WHERE i_programada_min.paciente_id = pacientes.id
                                  AND i_programada_min.nueva_fecha_contacto IS NOT NULL
                                  AND i_programada_min.deleted_at IS NULL
                            )
                        ");
                });
            });
        });
    }

    /**
     * Cuenta alertas pendientes de los pacientes dentro del alcance de un usuario.
     */
    private function countAlertasPacientes(User $user): int
    {
        return $this->queryPacientesByUsuario($user)
            ->join('alertas', 'alertas.paciente_id', '=', 'pacientes.id')
            ->whereNull('alertas.revisado')
            ->whereNull('alertas.deleted_at')
            ->count('alertas.id');
    }
}
