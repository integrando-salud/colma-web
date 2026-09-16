<?php

use App\Enums\MotivoInteraccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La cita de entrenamiento programada es el próximo contacto. Corrige los
     * registros creados cuando la fecha se calculaba desde el día del contacto.
     */
    public function up(): void
    {
        DB::table('interacciones')
            ->where('motivo', MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value)
            ->where('contactado', true)
            ->whereNotNull('fecha_cita')
            ->where(function ($query) {
                $query->whereNull('nueva_fecha_contacto')
                    ->orWhereColumn('nueva_fecha_contacto', '!=', 'fecha_cita');
            })
            ->update(['nueva_fecha_contacto' => DB::raw('fecha_cita')]);
    }

    /** La corrección de datos no se revierte para conservar el historial coherente. */
    public function down(): void
    {
        // No-op.
    }
};
