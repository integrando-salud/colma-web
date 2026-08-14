<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimize retrieval of a patient's most recent pressure measurements.
     */
    public function up(): void
    {
        Schema::table('presion_arterial', function (Blueprint $table) {
            $table->index(['paciente_id', 'fecha_registro']);
        });
    }

    /**
     * Remove the index added for the recent-measurements query.
     */
    public function down(): void
    {
        Schema::table('presion_arterial', function (Blueprint $table) {
            $table->dropIndex(['paciente_id', 'fecha_registro']);
        });
    }
};
