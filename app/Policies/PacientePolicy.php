<?php

namespace App\Policies;

use App\Models\Paciente;
use App\Models\User;

class PacientePolicy
{
    /** Determina si el usuario puede acceder al módulo de pacientes. */
    public function viewAny(User $usuario): bool
    {
        return $this->esPersonalDelPanel($usuario);
    }

    /** Determina si el paciente se encuentra dentro de su alcance asistencial. */
    public function view(User $usuario, Paciente $paciente): bool
    {
        return Paciente::query()
            ->visibleTo($usuario)
            ->whereKey($paciente->getKey())
            ->exists();
    }

    public function create(User $usuario): bool
    {
        return $this->esPersonalDelPanel($usuario);
    }

    public function update(User $usuario, Paciente $paciente): bool
    {
        return $this->view($usuario, $paciente);
    }

    /** Solo superusuarios y supervisores pueden activar o desactivar pacientes. */
    public function changeStatus(User $usuario, Paciente $paciente): bool
    {
        return $usuario->isA(User::ROL_SUPERUSUARIO, User::ROL_SUPERVISOR)
            && $this->view($usuario, $paciente);
    }

    private function esPersonalDelPanel(User $usuario): bool
    {
        return $usuario->isA(
            User::ROL_SUPERUSUARIO,
            User::ROL_SUPERVISOR_PAIS,
            User::ROL_SUPERVISOR,
            User::ROL_PROMOTOR,
        );
    }
}
