<?php

namespace App\Console\Commands;

use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Alerta;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AppointmentAlerts extends Command
{
    protected $signature = 'colma:raise_appointment_alerts {--chunk=500} {--dry-run}';

    protected $description = 'Raise alerts for missed appointments are appointments not yet completed nearing their deadline.';

    // time in days for recipient of this alert to react to it
    const DAYS_TO_REACT = 2;
    // time in days until due date that triggers a warning
    // Make sure this value is always larger than DAYS_TO_REACT
    const DAYS_FOR_WARNING = 7;

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Checking for missed appointments and appointments nearing due date...');


        DB::table('interacciones')
            ->select('paciente_id')
            ->distinct()
            ->orderBy('paciente_id')
            ->chunk(100, function ($patients) use ($dryRun) {
                foreach ($patients as $p) {
                    // Deal with appointments per patient
                    $appointments = Interaccion::where('paciente_id', $p->paciente_id)->get();
                    // TODO: check if there can only be one Entrenamiento record per patient
                    $visitSchedule = Entrenamiento::select('*')->where('paciente_id', $p->paciente_id)->get()->first();
                    $monthly_appointment_count = 0;
                    foreach ($appointments as $appointment) {
                        if ($appointment->contactado) {
                            if ($appointment->isMonthlyAppointment())
                                $monthly_appointment_count++;
                        } else {
                            $result= self::getAppointmentRecommendation($appointment, $visitSchedule, $monthly_appointment_count+1,self::DAYS_FOR_WARNING);

                            if ($result !== null) {

                                $this->upsertAlerta(
                                    $appointment->paciente_id,
                                    'appointment',
                                    $appointment->id,
                                    $result['description'],
                                    $result['recommendation'],
                                    $dryRun
                                );
                            }
                        }
                    }

                }
            });
        return self::SUCCESS;
    }



    /*
     * Using the same function as in RecalcularAlertasClinicas but with tip changed to supervisory
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
            ->where('tipo', 'supervisory')
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
                'tipo' => 'supervisory',
                'entidad_id' => $entidadId,
                'entidad_tipo' => $entidadTipo,
                'descripcion' => $descripcion,
                'recomendacion' => $recomendacion,
                'fecha' => $fecha,
                'fecha_contacto' => $fecha->copy()->addDays(self::DAYS_TO_REACT)->format('Y-m-d'),
            ]);
        }

    }

    /***
     * Determine
     *
     * @param Interaccion $appointment
     * @param Entrenamiento $visitSchedule
     * @param int $monthly_appointment
     * @param int $bufferDays
     * @return string[]|null
     */
    function getAppointmentRecommendation(Interaccion $appointment, Entrenamiento $visitSchedule, int $monthly_appointment, int $bufferDays): ?array
    {
        $dates = $this->get_window($appointment->motivo, $visitSchedule, $monthly_appointment);
        if ($dates !== null) {

            $start = Carbon::parse($dates['start']);
            $due = Carbon::parse($dates['end']);

            // Calculate the buffer window start (due date minus buffer days)
            $bufferStart = $due->copy()->subDays($bufferDays);
            $now = Carbon::now();

            // Check if dueDate before today
            if ($due->isPast()) {
                return [
                    'description' => "Appointment due date is in the past",
                    'recommendation' => "Skip appointment and schedule next"
                ];
            }

            // Check if window just started
            //TODO: Ensure that CHWs and supervisors get the warning at different times (via user_id?)
            if ($start->isToday()) {
                return [
                    'description' => "Appointment now due",
                    'recommendation' => "You can now schedule the next appointment"
                ];
            }


            // Check if today is within buffer range
            if ($now->between($bufferStart, $due)) {
                return [
                    'description' => "Appointment nearing due date",
                    'recommendation' => "Contact CHW to plan appointment before the due date"
                ];
            }
        }
        return null;
    }

    /**
     * Work around to get windows in days for the appointments: retrieve the start and end days from
     * Entrenamiento object using motivo tag. In case of monthly meetings, choose correct one using the provided counter.
     *
     * @param string $motivo
     * @return array of window start and window end in days relative to training date
     */
    private function get_window(string $motivo, Entrenamiento $visitSchedule, int $monthlyVisitNumber ): ?array
    {
        switch ($motivo) {
            case 'Agendar cita de entrenamiento':
                return null;
            case 'Cita de entrenamiento':
                return null;
            case 'Seguimiento 2 semanas luego del entrenamiento':
                return ['start' => $visitSchedule->seguimiento_dos_semanas_inicio, 'end'=> $visitSchedule->seguimiento_dos_semanas_fin];
            //return [Entrenamiento::DIAS_SUMA['seguimiento_dos_semanas_inicio'], Entrenamiento::DIAS_SUMA['seguimiento_dos_semanas_fin']];
            case 'Seguimiento mensual':
                //figure out which monthy meeting.
                switch ($monthlyVisitNumber) {
                    case 1:
                        return ['start' => $visitSchedule->llamada_mensual_1_inicio, 'end'=> $visitSchedule->llamada_mensual_1_fin];
                    case 2:
                        return ['start' => $visitSchedule->llamada_mensual_2_inicio, 'end'=> $visitSchedule->llamada_mensual_2_fin];
                    case 3:
                        return ['start' => $visitSchedule->llamada_mensual_3_inicio, 'end'=> $visitSchedule->llamada_mensual_3_fin];
                    case 4:
                        return ['start' => $visitSchedule->llamada_mensual_4_inicio, 'end'=> $visitSchedule->llamada_mensual_4_fin];
                    case 5:
                        return ['start' => $visitSchedule->llamada_mensual_5_inicio, 'end'=> $visitSchedule->llamada_mensual_5_fin];
                }
                return null;
            case 'Agendar cita final':
                return null;
            case 'Cita final':
                return ['start' => $visitSchedule->agendar_cita_final_inicio, 'end' => $visitSchedule->agendar_cita_final_fin];
                //return [Entrenamiento::DIAS_SUMA['agendar_cita_final_inicio'], Entrenamiento::DIAS_SUMA['agendar_cita_final_fin']];
            case 'Re-entrenamiento':
                return null;
            case 'Contacto iniciado por el participante':
                return null;
        }
        return null;
    }
}
