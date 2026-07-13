<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UsuarioService
{
    public function queryGetUsuarios(array $filters = [])
    {
        $authUser = auth()->user();

        $query = User::with(['pais', 'centroSalud', 'roles'])
            ->whereIsNot('paciente');

        if ($authUser->isA('supervisor')) {
            $query->where('supervisor_id', $authUser->id)
                ->where('pais_id', $authUser->pais_id);
        } elseif ($authUser->isA('supervisor_pais')) {
            $query->where('pais_id', $authUser->pais_id)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', [
                        'supervisor',
                        'promotor'
                    ]);
                });
        }

        if (!empty($filters['rol'])) {
            $query->whereHas('roles', function ($query) use ($filters) {
                $query->where('name', $filters['rol']);
            });
        }

        if (!empty($filters['pais']) && !$authUser->isA('supervisor') && !$authUser->isA('supervisor_pais')) {
            $query->where('pais_id', $filters['pais']);
        }

        if (!empty($filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $filters['nombre'] . '%');
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        switch ($orderBy) {
            case 'tipo':
                $query->select('users.*')
                    ->leftJoin('assigned_roles', function ($join) {
                        $join->on('users.id', '=', 'assigned_roles.entity_id')
                            ->where('assigned_roles.entity_type', '=', User::class);
                    })
                    ->leftJoin('roles', 'roles.id', '=', 'assigned_roles.role_id')
                    ->orderBy('roles.title', $orderDir);
                break;
            case 'pais':
                $query->select('users.*')
                    ->leftJoin('paises', 'paises.id', '=', 'users.pais_id')
                    ->orderBy('paises.nombre', $orderDir);
                break;
            case 'centro_salud':
                $query->select('users.*')
                    ->leftJoin('centros_salud', 'centros_salud.id', '=', 'users.centro_salud_id')
                    ->orderBy('centros_salud.nombre', $orderDir);
                break;
            case 'supervisor':
                $query->select('users.*')
                    ->leftJoin('users as supervisors', 'supervisors.id', '=', 'users.supervisor_id')
                    ->orderBy('supervisors.nombre', $orderDir);
                break;
            case 'pacientes':
                $query->select('users.*')
                    ->selectRaw(
                        "CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'superusuario'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM pacientes p
                                WHERE p.deleted_at IS NULL
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'supervisor_pais'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM pacientes p
                                INNER JOIN centros_salud cs ON cs.id = p.centro_salud_id
                                WHERE p.deleted_at IS NULL
                                  AND cs.pais_id = users.pais_id
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'supervisor'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM pacientes p
                                WHERE p.deleted_at IS NULL
                                  AND p.promotor_id IN (
                                      SELECT up.id
                                      FROM users up
                                      WHERE up.supervisor_id = users.id
                                        AND up.deleted_at IS NULL
                                  )
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'promotor'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM pacientes p
                                WHERE p.deleted_at IS NULL
                                  AND p.promotor_id = users.id
                            )
                            ELSE 0
                        END AS pacientes_count",
                        [User::class, User::class, User::class, User::class]
                    )
                    ->orderBy('pacientes_count', $orderDir);
                break;
            case 'alertas':
                $query->select('users.*')
                    ->selectRaw(
                        "CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'superusuario'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM alertas a
                                INNER JOIN pacientes p ON p.id = a.paciente_id
                                WHERE a.revisado IS NULL
                                  AND a.deleted_at IS NULL
                                  AND p.deleted_at IS NULL
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'supervisor_pais'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM alertas a
                                INNER JOIN pacientes p ON p.id = a.paciente_id
                                INNER JOIN centros_salud cs ON cs.id = p.centro_salud_id
                                WHERE a.revisado IS NULL
                                  AND a.deleted_at IS NULL
                                  AND p.deleted_at IS NULL
                                  AND cs.pais_id = users.pais_id
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'supervisor'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM alertas a
                                INNER JOIN pacientes p ON p.id = a.paciente_id
                                WHERE a.revisado IS NULL
                                  AND a.deleted_at IS NULL
                                  AND p.deleted_at IS NULL
                                  AND p.promotor_id IN (
                                      SELECT up.id
                                      FROM users up
                                      WHERE up.supervisor_id = users.id
                                        AND up.deleted_at IS NULL
                                  )
                            )
                            WHEN EXISTS (
                                SELECT 1
                                FROM assigned_roles ar
                                INNER JOIN roles r ON r.id = ar.role_id
                                WHERE ar.entity_id = users.id
                                  AND ar.entity_type = ?
                                  AND r.name = 'promotor'
                            ) THEN (
                                SELECT COUNT(*)
                                FROM alertas a
                                INNER JOIN pacientes p ON p.id = a.paciente_id
                                WHERE a.revisado IS NULL
                                  AND a.deleted_at IS NULL
                                  AND p.deleted_at IS NULL
                                  AND p.promotor_id = users.id
                            )
                            ELSE 0
                        END AS alertas_count",
                        [User::class, User::class, User::class, User::class]
                    )
                    ->orderBy('alertas_count', $orderDir);
                break;
            default:
                $query->orderBy($orderBy, $orderDir);
        }

        return $query;
    }

    public function storeUsuario(array $data): User
    {
        $user = empty($data['id']) ? new User() : User::find($data['id']);
        $supervisor = ($data['rol'] === 'promotor' && !empty($data['supervisor_id']))
            ? User::find($data['supervisor_id'])
            : null;

        $user->nombre = $data['nombre'];
        $user->sexo = $data['sexo'];
        $user->documento = $data['documento'];
        $user->email = $data['email'];
        $user->pais_id = $supervisor?->pais_id ?? $data['pais_id'];
        $user->centro_salud_id = $data['centro_salud_id'] ?? null;
        $user->supervisor_id = $data['supervisor_id'] ?? null;

        if (empty($data['id']) || !empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        Bouncer::sync($user)->roles([$data['rol']]);

        return $user;
    }

    public function deleteUsuario(User $user): bool
    {
        return $user->delete();
    }

    public function reactivateUsuario(User $user): bool
    {
        return $user->restore();
    }

    public function updatePassword(User $user, string $password): bool
    {
        $user->password = $password;
        return $user->save();
    }

    /**
     * Obtiene el número de pacientes de un usuario
     * Si el usuario es un Promotor, devuelve el número de pacientes que tiene asociados
     * Si el usuario es un Supervisor, devuelve la suma del número de pacientes que tiene asociado cada uno de sus promotores a cargo
     * Si el usuario es un Supervisor País, devuelve el número de pacientes vinculado a un centro de salud del mismo país que el usuario
     *
     * @param int $userId
     * @return int
     */
    public function countPacientes(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        if ($user->isA('promotor')) {
            return $user->pacientes->count();
        }

        if ($user->isA('supervisor')) {
            return $user->promotores->reduce(function ($carry, $promotor) {
                return $carry + $promotor->pacientes->count();
            }, 0);
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::whereHas('centroSalud', function ($query) use ($user) {
                $query->where('pais_id', $user->pais_id);
            })->count();
        }

        if ($user->isA('superusuario')) {
            return Paciente::count();
        }

        return 0;
    }

    /**
     * Igual que countPacientes(), pero solo contabiliza los pacientes activos
     *
     * @param int $userId
     * @return int
     */
    public function countPacientesActivos(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        if ($user->isA('promotor')) {
            return $user->pacientes->where('activo', true)->count();
        }

        if ($user->isA('supervisor')) {
            return $user->promotores->reduce(function ($carry, $promotor) {
                return $carry + $promotor->pacientes->where('activo', true)->count();
            }, 0);
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::where('activo', true)->whereHas('centroSalud', function ($query) use ($user) {
                $query->where('pais_id', $user->pais_id);
            })->count();
        }

        if ($user->isA('superusuario')) {
            return Paciente::where('activo', true)->count();
        }

        return 0;
    }

    /**
     * Obtiene el número total de pacientes con alertas pendientes
     *
     * @param int $userId
     * @return int
     */
    public function countPacientesConAlertas(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        if ($user->isA('promotor')) {
            return Paciente::where('activo', true)
                ->where('promotor_id', $user->id)
                ->whereHas('alertas', function ($query) {
                    $query->whereNull('revisado');
                })
                ->count();
        }

        if ($user->isA('supervisor')) {
            return Paciente::where('activo', true)
                ->whereIn('promotor_id', $user->promotores->pluck('id'))
                ->whereHas('alertas', function ($query) {
                    $query->whereNull('revisado');
                })
                ->count();
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::where('activo', true)
                ->whereHas('centroSalud', function ($query) use ($user) {
                    $query->where('pais_id', $user->pais_id);
                })
                ->whereHas('alertas', function ($query) {
                    $query->whereNull('revisado');
                })
                ->count();
        }

        if ($user->isA('superusuario')) {
            return Paciente::where('activo', true)
                ->whereHas('alertas', function ($query) {
                    $query->whereNull('revisado');
                })
                ->count();
        }

        return 0;
    }

    /**
     * Obtiene el número de alertas de los pacientes asignados a un usuario
     * Si el usuario es un Promotor, devuelve la suma del número de alertas de los pacientes que tiene asociados
     * Si el usuario es un Supervisor, devuelve la suma del número de alertas de todos los pacientes de todos los promotores que tiene asociados
     *
     * @param int $userId
     * @return int
     */
    public function countAlertas(int $userId): int
    {
        $user = User::withTrashed()->find($userId);

        if ($user->isA('promotor')) {
            return $this->countAlertasPacientes(function (Builder $query) use ($user) {
                $query->where('pacientes.promotor_id', $user->id);
            });
        }

        if ($user->isA('supervisor')) {
            return $this->countAlertasPacientes(function (Builder $query) use ($user) {
                $query->whereIn('pacientes.promotor_id', $user->promotores->pluck('id'));
            });
        }

        if ($user->isA('supervisor_pais')) {
            return $this->countAlertasPacientes(function (Builder $query) use ($user) {
                $query->whereHas('centroSalud', function ($query) use ($user) {
                    $query->where('pais_id', $user->pais_id);
                });
            });
        }

        if ($user->isA('superusuario')) {
            return $this->countAlertasPacientes();
        }

        return 0;
    }

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

    private function queryPacientesActivosByUsuario(User $user): ?Builder
    {
        if ($user->isA('promotor')) {
            return Paciente::query()
                ->where('activo', true)
                ->where('promotor_id', $user->id);
        }

        if ($user->isA('supervisor')) {
            return Paciente::query()
                ->where('activo', true)
                ->whereIn('promotor_id', $user->promotores->pluck('id'));
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::query()
                ->where('activo', true)
                ->whereHas('centroSalud', function ($query) use ($user) {
                    $query->where('pais_id', $user->pais_id);
                });
        }

        if ($user->isA('superusuario')) {
            return Paciente::query()->where('activo', true);
        }

        return null;
    }

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

    private function countAlertasPacientes(?callable $callback = null): int
    {
        $query = Paciente::query()
            ->join('alertas', 'alertas.paciente_id', '=', 'pacientes.id')
            ->whereNull('alertas.revisado')
            ->whereNull('alertas.deleted_at');

        if ($callback) {
            $callback($query);
        }

        return $query->count('alertas.id');
    }
}
