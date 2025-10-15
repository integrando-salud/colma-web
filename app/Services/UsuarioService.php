<?php

namespace App\Services;

use App\Models\User;

class UsuarioService
{
    public function queryGetUsuarios(array $filters = [])
    {
        $query = User::with(['pais', 'centroSalud', 'roles'])
            ->whereIsNot(['paciente']);

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

    public function updatePassword(User $user, string $password): bool
    {
        $user->password = $password;
        return $user->save();
    }
}
