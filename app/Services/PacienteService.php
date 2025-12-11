<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class PacienteService
{
    public function queryGetPacientes(array $filters = [])
    {
        $query = Paciente::query();

        if (auth()->user()->isA('promotor')) {
            $query->where('promotor_id', auth()->user()->id);
        } elseif (auth()->user()->isA('supervisor')) {
            $query->whereIn('promotor_id', auth()->user()->promotores->pluck('id'));
        } elseif (auth()->user()->isA('supervisor_pais')) {
            $query->whereHas('centroSalud', function (Builder $query) {
                $query->where('pais_id', auth()->user()->pais_id);
            });
        }

        $query->withCount(['alertas AS numero_alertas' => function (Builder $query) {
            $query->whereNull('revisado');
        }]);

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

        $query->orderByDesc('pacientes.activo');
        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        switch ($orderBy) {
            case 'sesiones':
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
            $paciente->telefono2 = data_get($data, 'telefono2');
            $paciente->email = $data['email'];
            $paciente->direccion = $data['direccion'];
            $paciente->referencia = data_get($data, 'referencia');
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

    public function getDatosClinicos(Paciente|int $paciente, $cantidad = 10, $fechaDesde = null, $fechaHasta = null)
    {
        if (is_int($paciente)) {
            $paciente = Paciente::find($paciente);
        }

        if (!$fechaDesde) {
            $fechaDesde = $paciente->fecha_enrolamiento;
        }

        if (!$fechaHasta) {
            $fechaHasta = now();
        }

        $cantidad = (int) $cantidad;

        return DB::select("
            WITH fecha_base AS (
                SELECT DATE(fecha_registro) AS fecha
                FROM glucosa
                WHERE paciente_id = :paciente_id_fechas_glucosa
                    AND fecha_registro BETWEEN :fecha_inicio_fechas_glucosa AND :fecha_fin_fechas_glucosa

                UNION

                SELECT DATE(fecha_registro) AS fecha
                FROM presion_arterial
                WHERE paciente_id = :paciente_id_fechas_presion
                    AND fecha_registro BETWEEN :fecha_inicio_fechas_presion AND :fecha_fin_fechas_presion
            ),
            fechas AS (
                SELECT fecha
                FROM fecha_base
                GROUP BY fecha
                ORDER BY fecha DESC
                LIMIT $cantidad
            ),
            ult_glucosa AS (
                SELECT * FROM (
                    SELECT
                        id, paciente_id, valor, ayuno,
                        DATE(fecha_registro) AS fecha,
                        fecha_registro,
                        ROW_NUMBER() OVER (PARTITION BY DATE(fecha_registro) ORDER BY fecha_registro DESC) AS rn
                    FROM glucosa
                    WHERE paciente_id = :paciente_id_ultima_glucosa
                        AND fecha_registro BETWEEN :fecha_inicio_ultima_glucosa AND :fecha_fin_ultima_glucosa
                ) t
                WHERE rn = 1
            ),
            ult_presion AS (
                SELECT * FROM (
                    SELECT
                        id, paciente_id, presion_sistolica, presion_diastolica,
                        DATE(fecha_registro) AS fecha,
                        fecha_registro,
                        ROW_NUMBER() OVER (PARTITION BY DATE(fecha_registro) ORDER BY fecha_registro DESC) AS rn
                    FROM presion_arterial
                    WHERE paciente_id = :paciente_id_ultima_presion
                      AND fecha_registro BETWEEN :fecha_inicio_ultima_presion AND :fecha_fin_ultima_presion
                ) t
                WHERE rn = 1
            )
            SELECT
                DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
                g.valor AS glucosa_valor,
                g.ayuno AS glucosa_ayuno,
                p.presion_sistolica,
                p.presion_diastolica
            FROM fechas f
            LEFT JOIN ult_glucosa g ON g.fecha = f.fecha
            LEFT JOIN ult_presion p ON p.fecha = f.fecha
            ORDER BY f.fecha DESC
        ", [
            'paciente_id_fechas_glucosa' => $paciente->id,
            'fecha_inicio_fechas_glucosa' => $fechaDesde,
            'fecha_fin_fechas_glucosa' => $fechaHasta,
            'paciente_id_fechas_presion' => $paciente->id,
            'fecha_inicio_fechas_presion' => $fechaDesde,
            'fecha_fin_fechas_presion' => $fechaHasta,
            'paciente_id_ultima_glucosa' => $paciente->id,
            'fecha_inicio_ultima_glucosa' => $fechaDesde,
            'fecha_fin_ultima_glucosa' => $fechaHasta,
            'paciente_id_ultima_presion' => $paciente->id,
            'fecha_inicio_ultima_presion' => $fechaDesde,
            'fecha_fin_ultima_presion' => $fechaHasta,
        ]);
    }

    public function cambiarEstado(Paciente $paciente): bool
    {
        $paciente->activo = !$paciente->activo;

        return $paciente->save();
    }
}
