<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class PacienteService
{
    public function queryGetPacientes(array $filters = [])
    {
        $query = Paciente::query();

        if (!empty($filters['nombre'])) {
            $query->where(function ($query) use ($filters) {
                $nombre = '%' . $filters['nombre'] . '%';
                $query->where('nombre', 'like', $nombre)
                    ->orWhere('apellido', 'like', $nombre);
            });
        }

        if (isset($filters['estado']) && is_numeric($filters['estado'])) {
            $query->where('activo', $filters['estado']);
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        switch ($orderBy) {
            case 'sesiones':
                break;
            case 'alertas':
                break;
            case 'ingresos':
                $query->select('pacientes.*')
                    ->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.accesos', $orderDir);
                break;
            case 'ultimo_ingreso':
                $query->select('pacientes.*')
                    ->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.ultimo_acceso', $orderDir);
                break;
            default:
                $query->orderBy($orderBy, $orderDir);
        }

        return $query;
    }

    public function storePaciente(array $data): Paciente
    {
        try {
            DB::beginTransaction();

            $paciente = empty($data['id']) ? new Paciente() : Paciente::find($data['id']);
            $paciente->nombre = $data['nombre'];
            $paciente->apellido = $data['apellido'];
            $paciente->fecha_nacimiento = $data['fecha_nacimiento'];
            $paciente->sexo = $data['sexo'];
            $paciente->documento = $data['documento'];
            $paciente->telefono = $data['telefono'];
            $paciente->email = $data['email'];
            $paciente->direccion = $data['direccion'];
            $paciente->centro_salud_id = $data['centro_salud_id'];
            $paciente->promotor_id = $data['promotor_id'];
            $paciente->fecha_enrolamiento = $data['fecha_enrolamiento'];
            $paciente->paquete_id = data_get($data, 'paquete_id');

            if (empty($data['id'])) {
                $usuario = $this->createUsuario($paciente, $data['password']);
                $paciente->user_id = $usuario->id;
            } else {
                $this->updateUsuario($paciente);
            }

            $paciente->save();
            $this->syncDiagnosticos($paciente, $data['diagnosticos']);
            DB::commit();

            return $paciente;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function createUsuario(Paciente $paciente, string $password): User
    {
        $usuarioService = new UsuarioService();
        $data = $this->fillDataUsuario($paciente, $password);

        return $usuarioService->storeUsuario($data);
    }

    private function updateUsuario(Paciente $paciente)
    {
        $usuarioService = new UsuarioService();
        $data = array_merge($this->fillDataUsuario($paciente), ['id' => $paciente->usuario->id]);

        return $usuarioService->storeUsuario($data);
    }

    private function fillDataUsuario(Paciente $paciente, ?string $password = null): array
    {
        $data = [
            'rol' => 'paciente',
            'nombre' => "$paciente->nombre $paciente->apellido",
            'sexo' => "$paciente->sexo",
            'documento' => "$paciente->documento",
            'email' => $paciente->email,
            'pais_id' => $paciente->centroSalud->pais_id,
            'centro_salud_id' => $paciente->centro_salud_id,
        ];

        if ($password) {
            $data['password'] = $password;
        }

        return $data;
    }

    private function syncDiagnosticos(Paciente $paciente, array $diagnosticos)
    {
        $paciente->diagnosticos()->sync($diagnosticos);
    }
}
