<?php

namespace App\Console\Commands;

use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Alerta;
use App\Models\Paciente;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * This Command should run exactly once every day at the start of the day.
 * The code does not prevent multiple database entries in case the Command is run more often.
 */
class AppointmentAlerts extends Command
{
    protected $signature = 'colma:raise_appointment_alerts {--chunk=500} {--dry-run}';

    protected $description = 'Raise alerts related to appointments.';

    // time in days for recipient of this alert to react to it
    const DAYS_TO_REACT = 2;

    // time in days until due date that triggers a warning
    // Make sure this value is always larger than DAYS_TO_REACT
    const DAYS_FOR_WARNING = 7;

    // Days after a window has started before the warning to schedule is sent:
    const DAYS_FOR_SCHEDULE_WARNING = 3;
    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $warning_counter = 0;

        $this->info('Checking for windows opening..');


        Paciente::chunk($chunkSize, function ($patients) use ($dryRun) {
            foreach ($patients as $p) {
                // Get last last appointment for each patient
                $appointment = $p->interacciones()->latest('fecha')->first();
                if ($appointment !== null) {
                    // TODO: the relationship in Paciente model suggests a hasmany relationship with Entrenamiento. Which one should be picked?
                    $test = Entrenamiento::all();
                    $visitSchedule = $p->entrenamientos()->latest('fecha')->first();
                    $result = self::getNextAppointmentReminder($appointment, $visitSchedule);
                    if ($result !== null) {
                        $this->upsertAlerta(
                            $appointment->paciente_id,
                            'appointment',
                            //TODO: Does it make sense to tie this warning to an appointment?
                            $appointment->id,
                            $result['description'],
                            $result['recommendation'],
                            $dryRun
                        );
                        $warning_counter++;
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
        bool $dryRun
    ): void {
        $alerta = Alerta::query()
            ->where('paciente_id', $pacienteId)
            ->where('tipo', 'operativa')
            ->where('entidad_tipo', $entidadTipo)
            ->where('entidad_id', $entidadId)
            ->first();

        if ($alerta) {
            if (!$dryRun) {
                $fechaBase = now();
                $alerta->update([
                    'recomendacion' => $recomendacion,
                    'descripcion' => $descripcion,
                    'fecha_contacto' => $fechaBase->copy()->addDays(self::DAYS_TO_REACT)->format('Y-m-d'),
                ]);
            }

            return;
        }

        if (!$dryRun) {
            $fecha = now();
            Alerta::create([
                'paciente_id' => $pacienteId,
                'tipo' => 'operativa',
                'entidad_id' => $entidadId,
                'entidad_tipo' => $entidadTipo,
                'descripcion' => $descripcion,
                'recomendacion' => $recomendacion,
                'fecha' => $fecha,
                'fecha_contacto' => $fecha->copy()->addDays(self::DAYS_TO_REACT)->format('Y-m-d'),
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
                //TODO: Ensure that CHWs and supervisors get the warning at different times (via user_id?)
                if ($start->copy()->startOfDay()->diffInDays(now()->copy()->startOfDay(),false)==-self::DAYS_FOR_SCHEDULE_WARNING) {
                    return [
                        'description' => "Appointment $nextAppointment_motivo can now be scheduled",
                        'recommendation' => "You can now schedule appointment $nextAppointment_motivo"
                    ];
                }
            }
        }
        return null;
    }

    //TODO: Not currently used but starting point for further warnings should these be required.
        /***
     * This function generates a warning when the window for the next appointment for a patient starts.
     * Note that this function could be used for other date related checks, such as checking that the chosen due date is inside the window,
     * or that an appointment has not been scheduled for a patient, or when the date for the latest appointment (for each patient) becomes
     * a date in the past..
     *
     * @param Interaccion $appointment
     * @param Entrenamiento $visitSchedule
     * @param int $monthly_appointment
     * @param int $bufferDays
     * @return string[]|null
     */
    private function getAppointmentRecommendation(String $appointment_motivo, Entrenamiento $visitSchedule, int $bufferDays): ?array
    {

        $dates = $this->get_window($appointment_motivo, $visitSchedule);
        if ($dates !== null) {

            $start = Carbon::parse($dates['start']);
            $due = Carbon::parse($dates['end']);

            // Calculate the buffer window start (due date minus buffer days)
            $bufferStart = $due->copy()->subDays($bufferDays);
            $now = Carbon::now();

            // Check if window just started
            //TODO: Ensure that CHWs and supervisors get the warning at different times (via user_id?)
            if ($start->isToday()) {
                return [
                    'description' => "Appointment $appointment_motivo now due",
                    'recommendation' => "You can now schedule appointment $appointment_motivo"
                ];
            }


/*            // Check if dueDate before today
            if ($due->isPast()) {
                return [
                    'description' => "Appointment $appointment->motivo due date is in the past",
                    'recommendation' => "Skip appointment $appointment->motivo and schedule next"
                ];
            }

            // Check if today is within buffer range
            if ($now->between($bufferStart, $due)) {
                return [
                    'description' => "Appointment $appointment->motivo nearing due date",
                    'recommendation' => "Contact CHW to plan appointment $appointment->motivo before the due date"
                ];
            }*/
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
}
