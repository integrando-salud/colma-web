<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use App\Support\UserTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class IndicadorService
{
    /**
     * Obtiene los indicadores generales visibles para el usuario del panel.
     *
     * @return array{total_participantes: int, total_participantes_activos: int, porcentaje_participantes_activos: float}
     */
    public function getIndicadoresGenerales(User $usuario): array
    {
        $pacientes = Paciente::query()->visibleTo($usuario);
        $totalParticipantes = (clone $pacientes)->count();
        $totalParticipantesActivos = (clone $pacientes)->where('activo', true)->count();

        return [
            'total_participantes' => $totalParticipantes,
            'total_participantes_activos' => $totalParticipantesActivos,
            'porcentaje_participantes_activos' => $totalParticipantes === 0
                ? 0
                : round(($totalParticipantesActivos / $totalParticipantes) * 100, 2),
        ];
    }

    /**
     * Obtiene los indicadores de entrenamiento visibles para el usuario del panel.
     *
     * Un participante se considera entrenado cuando cuenta con una cita final
     * contactada. La existencia de varias citas no altera el conteo del paciente.
     *
     * @return array{participantes_entrenados: int}
     */
    public function getIndicadoresEntrenamiento(User $usuario): array
    {
        $participantesEntrenados = Paciente::query()
            ->visibleTo($usuario)
            ->where('activo', true)
            ->whereHas('interacciones', function ($query) {
                $query->where('motivo', MotivoInteraccion::CITA_FINAL->value)
                    ->where('contactado', true);
            })
            ->count();

        return [
            'participantes_entrenados' => $participantesEntrenados,
        ];
    }

    /**
     * Obtiene los indicadores de interacciones programadas visibles para el usuario.
     *
     * @return array{porcentaje_seguimiento_15_dias_realizado: float}
     */
    public function getIndicadoresInteraccionesProgramadas(User $usuario): array
    {
        $pacientes = Paciente::query()->visibleTo($usuario);
        $totalParticipantes = (clone $pacientes)->count();
        $participantesConSeguimiento15DiasRealizado = (clone $pacientes)
            ->whereHas('interacciones', function ($query) {
                $query->where('motivo', MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS->value)
                    ->where('contactado', true);
            })
            ->count();
        $participantesConLlamadoTelefonico = (clone $pacientes)
            ->whereHas('interacciones', function ($query) {
                $query->where('modalidad', Interaccion::MODALIDADES[0])
                    ->whereNull('alerta_id');
            })
            ->count();
        $participantesConLlamadoExitoso = (clone $pacientes)
            ->whereHas('interacciones', function ($query) {
                $query->where('contactado', true)
                    ->where('modalidad', Interaccion::MODALIDADES[0])
                    ->whereNull('alerta_id');
            })
            ->count();

        return [
            'porcentaje_seguimiento_15_dias_realizado' => $totalParticipantes === 0
                ? 0
                : round(($participantesConSeguimiento15DiasRealizado / $totalParticipantes) * 100, 2),
            'porcentaje_seguimiento_mensual_realizado' => $this->porcentajeSeguimientosMensuales($pacientes, $totalParticipantes, 1),
            'porcentaje_seguimiento_2_meses_realizado' => $this->porcentajeSeguimientosMensuales($pacientes, $totalParticipantes, 2),
            'porcentaje_seguimiento_3_meses_realizado' => $this->porcentajeSeguimientosMensuales($pacientes, $totalParticipantes, 3),
            'porcentaje_seguimiento_4_meses_realizado' => $this->porcentajeSeguimientosMensuales($pacientes, $totalParticipantes, 4),
            'porcentaje_contacto_exitoso_via_llamado_telefonico' => $participantesConLlamadoTelefonico === 0
                ? 0
                : round(($participantesConLlamadoExitoso / $participantesConLlamadoTelefonico) * 100, 2),
        ];
    }

    /**
     * Calcula el porcentaje de pacientes con una cantidad mínima de seguimientos
     * mensuales contactados, sin contar dos veces al mismo participante.
     */
    private function porcentajeSeguimientosMensuales(Builder $pacientes, int $totalParticipantes, int $cantidadMinima): float
    {
        if ($totalParticipantes === 0) {
            return 0.0;
        }

        $participantesConSeguimiento = (clone $pacientes)
            ->whereHas('interacciones', function ($query) {
                $query->where('motivo', MotivoInteraccion::SEGUIMIENTO_MENSUAL->value)
                    ->where('contactado', true);
            }, '>=', $cantidadMinima)
            ->count();

        return round(($participantesConSeguimiento / $totalParticipantes) * 100, 2);
    }

    /**
     * Obtiene los indicadores de interacciones asociadas a alertas clínicas.
     *
     * @return array{interacciones_alertas_clinicas_contactadas: int, porcentaje_alertas_clinicas_contactadas: float|int}
     */
    public function getIndicadoresAlertasClinicas(User $usuario): array
    {
        $totalAlertasClinicas = Alerta::query()
            ->where('tipo', 'clinica')
            ->whereHas('paciente', fn (Builder $query) => $query->visibleTo($usuario))
            ->count();
        $interaccionesContactadas = Interaccion::query()
            ->where('contactado', true)
            ->whereHas('paciente', fn (Builder $query) => $query->visibleTo($usuario))
            ->whereHas('alerta', fn (Builder $query) => $query->where('tipo', 'clinica'))
            ->count();
        $interaccionesContactadasDentroDe48Horas = $this->contarInteraccionesEnPlazo($usuario, 'clinica', 48);

        return [
            'interacciones_alertas_clinicas_contactadas' => $interaccionesContactadas,
            'porcentaje_alertas_clinicas_contactadas' => $totalAlertasClinicas === 0
                ? 0
                : round(($interaccionesContactadas / $totalAlertasClinicas) * 100, 2),
            'interacciones_alertas_clinicas_contactadas_48_horas' => $interaccionesContactadasDentroDe48Horas,
            'porcentaje_alertas_clinicas_contactadas_48_horas' => $totalAlertasClinicas === 0
                ? 0
                : round(($interaccionesContactadasDentroDe48Horas / $totalAlertasClinicas) * 100, 2),
        ];
    }

    /**
     * Obtiene los indicadores de interacciones asociadas a alertas operativas.
     *
     * @return array{interacciones_alertas_operativas_contactadas: int, porcentaje_alertas_operativas_contactadas: float|int, interacciones_alertas_operativas_contactadas_72_horas: int, porcentaje_alertas_operativas_contactadas_72_horas: float|int}
     */
    public function getIndicadoresAlertasOperativas(User $usuario): array
    {
        $totalAlertasOperativas = Alerta::query()
            ->where('tipo', 'operativa')
            ->whereHas('paciente', fn (Builder $query) => $query->visibleTo($usuario))
            ->count();
        $interaccionesContactadas = Interaccion::query()
            ->where('contactado', true)
            ->whereHas('paciente', fn (Builder $query) => $query->visibleTo($usuario))
            ->whereHas('alerta', fn (Builder $query) => $query->where('tipo', 'operativa'))
            ->count();
        $interaccionesContactadasDentroDe72Horas = $this->contarInteraccionesEnPlazo($usuario, 'operativa', 72);

        return [
            'interacciones_alertas_operativas_contactadas' => $interaccionesContactadas,
            'porcentaje_alertas_operativas_contactadas' => $totalAlertasOperativas === 0
                ? 0
                : round(($interaccionesContactadas / $totalAlertasOperativas) * 100, 2),
            'interacciones_alertas_operativas_contactadas_72_horas' => $interaccionesContactadasDentroDe72Horas,
            'porcentaje_alertas_operativas_contactadas_72_horas' => $totalAlertasOperativas === 0
                ? 0
                : round(($interaccionesContactadasDentroDe72Horas / $totalAlertasOperativas) * 100, 2),
        ];
    }

    /**
     * Obtiene los indicadores de uso de la aplicación visibles para el usuario.
     *
     * @return array{porcentaje_adherencia_sesiones: float|int}
     */
    public function getIndicadoresApp(User $usuario): array
    {
        $participantesActivos = Paciente::query()
            ->visibleTo($usuario)
            ->where('activo', true)
            ->with([
                'entrenamientos' => fn ($query) => $query
                    ->where('activo', true)
                    ->latest()
                    ->with('tratamientoVersion.sesiones.multimedia'),
                'multimediaReads',
            ])
            ->get();
        $totalParticipantesActivos = $participantesActivos->count();
        $participantesAdherentes = $participantesActivos
            ->filter(fn (Paciente $paciente) => $this->completoTodasLasSesionesHabilitadas($paciente))
            ->count();

        return [
            'porcentaje_adherencia_sesiones' => $totalParticipantesActivos === 0
                ? 0
                : round(($participantesAdherentes / $totalParticipantesActivos) * 100, 2),
        ];
    }

    /**
     * Cuenta interacciones locales dentro de una ventana UTC desde la alerta.
     * Las interacciones se modelan como fecha y hora locales del paciente, por
     * lo cual no pueden compararse directamente contra un timestamp UTC en SQL.
     */
    private function contarInteraccionesEnPlazo(User $usuario, string $tipoAlerta, int $horas): int
    {
        return Interaccion::query()
            ->where('contactado', true)
            ->whereHas('paciente', fn (Builder $query) => $query->visibleTo($usuario))
            ->whereHas('alerta', fn (Builder $query) => $query->where('tipo', $tipoAlerta))
            ->with(['alerta', 'paciente.centroSalud.pais'])
            ->get()
            ->filter(function (Interaccion $interaccion) use ($horas): bool {
                $alerta = $interaccion->alerta;
                $paciente = $interaccion->paciente;

                if (! $alerta || ! $alerta->created_at || ! $interaccion->fecha || ! $interaccion->hora) {
                    return false;
                }

                $timezone = $paciente->centroSalud?->pais?->codigo === 'PE'
                    ? UserTimezone::PERU
                    : UserTimezone::ARGENTINA;

                $fechaHora = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $interaccion->fecha->format('Y-m-d').' '.$interaccion->hora->format('H:i:s'),
                    $timezone,
                )->utc();

                return $fechaHora->betweenIncluded($alerta->created_at, $alerta->created_at->copy()->addHours($horas));
            })
            ->count();
    }

    /**
     * Verifica que el participante haya leído todos los archivos de cada sesión
     * habilitada de la versión asignada a su entrenamiento activo.
     */
    private function completoTodasLasSesionesHabilitadas(Paciente $paciente): bool
    {
        $entrenamiento = $paciente->entrenamientos->first();

        if (! $entrenamiento || ! $entrenamiento->tratamientoVersion || $entrenamiento->fecha->isFuture()) {
            return false;
        }

        $fechaInicio = $entrenamiento->fecha->copy()->startOfDay();
        $semanasHabilitadas = intdiv($fechaInicio->diffInDays(now()->startOfDay()), 7) + 1;
        $sesionesHabilitadas = $entrenamiento->tratamientoVersion->sesiones
            ->filter(fn ($sesion) => $sesion->semana <= $semanasHabilitadas)
            ->filter(fn ($sesion) => $sesion->multimedia->isNotEmpty());

        if ($sesionesHabilitadas->isEmpty()) {
            return false;
        }

        $multimediaLeidaIds = $paciente->multimediaReads
            ->where('entrenamiento_id', $entrenamiento->id)
            ->pluck('tratamiento_multimedia_id')
            ->filter()
            ->unique();

        return $sesionesHabilitadas->every(
            fn ($sesion) => $sesion->multimedia->pluck('id')->diff($multimediaLeidaIds)->isEmpty()
        );
    }
}
