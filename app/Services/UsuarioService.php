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
            $query->where('name', 'like', '%' . $filters['nombre'] . '%');
        }

        return $query->orderBy(data_get($filters, 'orderBy', 'id'), data_get($filters, 'orderDir', 'asc'));
    }

    public function storeUsuario(array $data): User
    {
        $user = empty($data['id']) ? new User() : User::find($data['id']);
        $user->name = $data['nombre'];
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
}
