<?php

namespace App\Console\Commands;

use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Alerta;
use App\Models\Paciente;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\PacienteService;

/**
 * This Command should run exactly once every day at the start of the day.
 * The code does not prevent multiple database entries in case the Command is run more often.
 */
class AppointmentAlerts extends Command
{
    protected $signature = 'colma:raise_appointment_alerts {--chunk=500} {--dry-run}';

    protected $description = 'Raise alerts related to appointments.';


    // time in days until due date that triggers a warning
    const DAYS_BEFORE_WINDOW_END_WARNING = 7;
    const DAYS_TO_REACT_TO_WINDOW_END_WARNING = 2;

    // Days after a window has started before the warning to schedule is sent:
    const DAYS_FOR_SCHEDULE_WARNING = 3;
    const DAYS_TO_REACT_TO_WINDOW_OPENING = 7;

    public function handle(PacienteService $pService): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');


        $this->info('Checking for windows opening..');

        $warning_counter = 0;
        Paciente::chunk($chunkSize, function ($patients) use ($dryRun, &$warning_counter, $pService) {
            foreach ($patients as $p) {
                // Get last appointment for each patient
                $appointment = $p->interacciones()->latest('fecha')->first();
                if ($appointment !== null) {
                    $visitSchedule = $pService->getEntrenamientoActual($p);
                    $result = self::getNextAppointmentReminder($appointment, $visitSchedule);
                    if ($result !== null) {
                        $this->upsertAlerta(
                            $appointment->paciente_id,
                            'appointment',
                            $appointment->id,
                            $result['description'],
                            $result['recommendation'],
                            now()->copy()->addDays(self::DAYS_TO_REACT_TO_WINDOW_OPENING)->format('Y-m-d'),
                            $dryRun
                        );
                        $warning_counter++;
                    } else {
                        $result = self::getWindowClosingReminder($appointment, $visitSchedule);
                        if ($result !== null) {
                            $this->upsertAlerta(
                                $appointment->paciente_id,
                                'window_end',
                                $appointment->id,
                                $result['description'],
                                $result['recommendation'],
                                now()->copy()->addDays(self::DAYS_TO_REACT_TO_WINDOW_END_WARNING)->format('Y-m-d'),
                                $dryRun
                            );
                            $warning_counter++;
                            $alerta = $p->alertas()
                                ->where('revisado', null)
                                ->where('entidad_tipo', 'appointment')
                                ->latest('fecha')->first();
                            if ($alerta !== null) {
                                if (!$dryRun) {
                                    $alerta->update([
                                        'revisado' => now(),
                                        'recomendacion' => "Canceled by the system upon window end warning"
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        });
        $this->info("{$warning_counter} warnings added to alerts table");

        return self::SUCCESS;
    }



    /*
     * Using the same function as in RecalcularAlertasClinicas but with tipo changed to supervisory
     */
    private function upsertAlerta(
        int $pacienteId,
        string $entidadTipo,
        int $entidadId,
        string $descripcion,
        string $recomendacion,
        string $fechaContacto,
        bool $dryRun
    ): void {
        $alerta = Alerta::query()
            ->where('paciente_id', $pacienteId)
            ->where('tipo', 'calls')
            ->where('entidad_tipo', $entidadTipo)
            ->where('entidad_id', $entidadId)
            ->first();

        if ($alerta) {
            if (!$dryRun) {
                $fechaBase = now();
                $alerta->update([
                    'recomendacion' => $recomendacion,
                    'descripcion' => $descripcion,
                    'fecha_contacto' => $fechaContacto,
                ]);
            }

            return;
        }

        if (!$dryRun) {
            $fecha = now();
            Alerta::create([
                'paciente_id' => $pacienteId,
                'tipo' => 'calls',
                'entidad_id' => $entidadId,
                'entidad_tipo' => $entidadTipo,
                'descripcion' => $descripcion,
                'recomendacion' => $recomendacion,
                'fecha' => $fecha,
                'fecha_contacto' => $fechaContacto,
            ]);
        }

    }

    /**
     * Provide reminder for next appointment if the past appointment has been completed and the window
     * for the next appointment has opened.
     *
     * @param Interaccion $appointment
     * @param Entrenamiento $visitSchedule
     * @return string[]|void|null
     */
    private function getNextAppointmentReminder(Interaccion $appointment, Entrenamiento $visitSchedule)
    {

        if ($appointment->contactado) {
            $nextAppointment_motivo = $appointment->nextAppointmentMotivo();
            $dates = $this->get_window($nextAppointment_motivo, $visitSchedule);
            if ($dates !== null) {

                $start = Carbon::parse($dates['start']);
                if ($start->copy()->startOfDay()->diffInDays(now()->copy()->startOfDay(),false)==self::DAYS_FOR_SCHEDULE_WARNING) {
                    return [
                        'description' => "Appointment $nextAppointment_motivo can now be scheduled",
                        'recommendation' => "You can now schedule appointment $nextAppointment_motivo"
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Generate a reminder that the window is closing if there is no appointment (regardless of contactado state) in the database
     * for the motivo associated with the currently active window
     *
     * @param Interaccion $appointment
     * @param Entrenamiento $visitSchedule
     * @return string[]|null
     */
    private function getWindowClosingReminder(Interaccion $appointment, Entrenamiento $visitSchedule)
    {
        $currentMotivo = $this->getCurrentMotivoBasedOnWindow($visitSchedule);
        $lastStoredMotivo = $appointment->motivo;
        if ($this->isMotivoGreater($currentMotivo, $lastStoredMotivo)) {

            $dates = $this->get_window($currentMotivo, $visitSchedule);
            if ($dates !== null) {

                $end = Carbon::parse($dates['end']);

                if (now()->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) == self::DAYS_BEFORE_WINDOW_END_WARNING) {
                    $days = self::DAYS_BEFORE_WINDOW_END_WARNING;
                    return [
                        'description' => "Appointment $currentMotivo reaching end of window",
                        'recommendation' => "You should schedule $currentMotivo in the next $days days",
                        ''
                    ];
                }
            }
        }
        return null;
    }


    /**
     * Work around to get windows in days for the appointments: retrieve the start and end days from
     * Entrenamiento object using motivo tag.
     *
     * @param string $motivo
     * @return array of window start and window end in days relative to training date
     */
    private function get_window(string $motivo, Entrenamiento $visitSchedule): ?array
    {
        switch ($motivo) {
            case 'Agendar cita de entrenamiento':
                return null;
            case 'Cita de entrenamiento':
                return null;
            case 'Seguimiento 2 semanas luego del entrenamiento':
                return ['start' => $visitSchedule->seguimiento_dos_semanas_inicio, 'end'=> $visitSchedule->seguimiento_dos_semanas_fin];
            case 'Seguimiento mensual 1':
                return ['start' => $visitSchedule->llamada_mensual_1_inicio, 'end'=> $visitSchedule->llamada_mensual_1_fin];
            case 'Seguimiento mensual 2':
                return ['start' => $visitSchedule->llamada_mensual_2_inicio, 'end'=> $visitSchedule->llamada_mensual_2_fin];
            case 'Seguimiento mensual 3':
                return ['start' => $visitSchedule->llamada_mensual_3_inicio, 'end'=> $visitSchedule->llamada_mensual_3_fin];
            case 'Seguimiento mensual 4':
                return ['start' => $visitSchedule->llamada_mensual_4_inicio, 'end'=> $visitSchedule->llamada_mensual_4_fin];
            case 'Seguimiento mensual 5':
                return ['start' => $visitSchedule->llamada_mensual_5_inicio, 'end'=> $visitSchedule->llamada_mensual_5_fin];
            case 'Agendar cita final':
                return null;
            case 'Cita final':
                return ['start' => $visitSchedule->agendar_cita_final_inicio, 'end' => $visitSchedule->agendar_cita_final_fin];
            case 'Re-entrenamiento':
                return null;
            case 'Contacto iniciado por el participante':
                return null;
        }
        return null;
    }

    private function getCurrentMotivoBasedOnWindow(Entrenamiento $visitSchedule) : ? string {
        $now = now();
        if ($now->between($visitSchedule->seguimiento_dos_semanas_inicio, $visitSchedule->seguimiento_dos_semanas_fin))
            return "Seguimiento 2 semanas luego del entrenamiento";
        if ($now->between($visitSchedule->llamada_mensual_1_inicio, $visitSchedule->llamada_mensual_1_fin))
            return "Seguimiento mensual 1";
        if ($now->between($visitSchedule->llamada_mensual_2_inicio, $visitSchedule->llamada_mensual_2_fin))
            return "Seguimiento mensual 2";
        if ($now->between($visitSchedule->llamada_mensual_3_inicio, $visitSchedule->llamada_mensual_3_fin))
            return "Seguimiento mensual 3";
        if ($now->between($visitSchedule->llamada_mensual_4_inicio, $visitSchedule->llamada_mensual_4_fin))
            return "Seguimiento mensual 4";
        if ($now->between($visitSchedule->llamada_mensual_5_inicio, $visitSchedule->llamada_mensual_5_fin))
            return "Seguimiento mensual 5";
        if ($now->between($visitSchedule->agendar_cita_final_inicio, $visitSchedule->agendar_cita_final_fin))
            return "Cita final";

        return null;
    }

    /** Check if motivo1 is greater (later session) than motivo2 */
    private function isMotivoGreater($motivo1, $motivo2) : bool {
        $index1 = array_search($motivo1, Interaccion::MOTIVOS_CONTACTO);
        $index2 = array_search($motivo2, Interaccion::MOTIVOS_CONTACTO);
        return ($index1 > $index2);
    }
}
