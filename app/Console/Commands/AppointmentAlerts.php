<?php

namespace App\Console\Commands;

use App\Models\Interaccion;
use App\Models\Alerta;
use Illuminate\Console\Command;
use Carbon\Carbon;


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

        Interaccion::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($appointments) use ($dryRun) {
                foreach ($appointments as $appointment) {
                    $result = self::getAppointmentRecommendation($appointment->contactado, $appointment->fecha_cita, self::DAYS_FOR_WARNING);
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
            });

        if ($dryRun) {
            $this->line('');
            $this->comment('Dry run: no se escribieron cambios.');
        }

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
    function getAppointmentRecommendation($isCompleted, $dueDate, $bufferDays): ?array
    {
        if ($isCompleted) {
            return null;
        }
        $due = Carbon::parse($dueDate);

        // Calculate the buffer window start (due date minus buffer days)
        $bufferStart = $due->copy()->subDays($bufferDays);
        $now = Carbon::now();

        // Check if dueDate before today
        if ($due->isPast()) {
            return [
                'description'=> "Appointment due date is in the past",
                'recommendation'=>"Skip appointment and schedule next"
                ];
        }

        // Check if today is within buffer range
        if ($now->between($bufferStart, $due)) {
            return [
                'description'=> "Appointment nearing due date",
                'recommendation'=>"Contact CHW to plan appointment before the due date"
                ];
        }

        return null;
    }
}
