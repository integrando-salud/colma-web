<?php

namespace App\Services;

use App\Models\User;

class UsuarioService
{
    public function queryGetUsuarios(array $filters = [])
    {
        $query = User::with(['pais', 'centroSalud', 'roles'])
            ->whereIsNot('paciente');

        return $query;
    }
}
