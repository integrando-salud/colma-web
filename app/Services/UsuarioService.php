<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;

class UsuarioService
{
    public function queryGetUsuarios(array $filters = [])
    {
        $query = User::with(['pais', 'centroSalud', 'roles'])
            ->whereIsNot(['paciente']);

        if (auth()->user()->isA('supervisor')) {
            $query->where('supervisor_id', auth()->user()->id);
        } elseif (auth()->user()->isA('supervisor_pais')) {
            $query->where('pais_id', auth()->user()->pais_id)
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

        if (!empty($filters['pais'])) {
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
//                $query->withCount('pacientes')
//                    ->orderBy('pacientes_count', $orderDir);
                break;
            case 'alertas':
//                $query->withCount('alertas')
//                    ->orderBy('alertas_count', $orderDir);
                break;
            default:
                $query->orderBy($orderBy, $orderDir);
        }

        return $query;
    }

    public function storeUsuario(array $data): User
    {
        $user = empty($data['id']) ? new User() : User::find($data['id']);
        $user->nombre = $data['nombre'];
        $user->sexo = $data['sexo'];
        $user->documento = $data['documento'];
        $user->email = $data['email'];
        $user->pais_id = $data['pais_id'];
        $user->centro_salud_id = $data['centro_salud_id'] ?? null;
        $user->supervisor_id = $data['supervisor_id'] ?? null;

        if (empty($data['id'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->assign($data['rol']);

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
            return $user->pacientes->where('activo', true)->reduce(function ($carry, $paciente) {
                return $carry + $paciente->alertas->whereNull('revisado')->count();
            }, 0);
        }

        if ($user->isA('supervisor')) {
            return $user->promotores->reduce(function ($carryPromotor, $promotor) {
                return $carryPromotor + $promotor->pacientes->where('activo', true)->reduce(function ($carryPaciente, $paciente) {
                    return $carryPaciente + $paciente->alertas->whereNull('revisado')->count();
                }, 0);
            }, 0);
        }

        if ($user->isA('supervisor_pais')) {
            return Paciente::where('activo', true)
                ->whereHas('centroSalud', function ($query) use ($user) {
                    $query->where('pais_id', $user->pais_id);
                })
                ->get()
                ->reduce(function ($carry, $paciente) {
                    return $carry + $paciente->alertas->whereNull('revisado')->count();
                }, 0);
        }

        if ($user->isA('superusuario')) {
            return Paciente::where('activo', true)
                ->get()
                ->reduce(function ($carry, $paciente) {
                    return $carry + $paciente->alertas->whereNull('revisado')->count();
                }, 0);
        }

        return 0;
    }
}
