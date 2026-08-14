<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determina si el actor puede modificar los datos de un usuario del panel.
     *
     * El alcance coincide con el listado: un supervisor administra solo a sus
     * promotores, un supervisor de país a supervisores y promotores de su país,
     * y un superusuario a cualquier usuario que no sea paciente.
     */
    public function update(User $actor, User $target): bool
    {
        return $this->puedeAdministrar($actor, $target);
    }

    /**
     * Determina si el actor puede eliminar lógicamente al usuario objetivo.
     *
     * Los supervisores no eliminan usuarios, en concordancia con las acciones
     * disponibles en la pantalla de administración.
     */
    public function delete(User $actor, User $target): bool
    {
        return $actor->isA(User::ROL_SUPERUSUARIO, User::ROL_SUPERVISOR_PAIS)
            && $this->puedeAdministrar($actor, $target);
    }

    /**
     * Determina si el actor puede restablecer la contraseña del usuario objetivo.
     */
    public function updatePassword(User $actor, User $target): bool
    {
        return $this->puedeAdministrar($actor, $target);
    }

    /**
     * Comprueba el alcance administrativo por rol sin depender de parámetros del cliente.
     */
    private function puedeAdministrar(User $actor, User $target): bool
    {
        if ($target->isA(User::ROL_PACIENTE)) {
            return false;
        }

        if ($actor->isA(User::ROL_SUPERUSUARIO)) {
            return true;
        }

        if ($actor->isA(User::ROL_SUPERVISOR_PAIS)) {
            return $target->pais_id === $actor->pais_id
                && $target->isA(User::ROL_SUPERVISOR, User::ROL_PROMOTOR);
        }

        return $actor->isA(User::ROL_SUPERVISOR)
            && $target->pais_id === $actor->pais_id
            && $target->supervisor_id === $actor->id
            && $target->isA(User::ROL_PROMOTOR);
    }
}
