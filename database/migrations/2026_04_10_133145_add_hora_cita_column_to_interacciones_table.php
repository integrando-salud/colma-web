<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->time('hora_cita')->nullable()->after('fecha_cita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->dropColumn('hora_cita');
        });
    }
};
