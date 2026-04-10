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
            $table->date('fecha_reentrenamiento')->nullable()->after('seguimiento_pregunta_e');
            $table->time('hora_reentrenamiento')->nullable()->after('fecha_reentrenamiento');
            $table->string('lugar_reentrenamiento')->nullable()->after('hora_reentrenamiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->dropColumn('fecha_reentrenamiento');
            $table->dropColumn('hora_reentrenamiento');
            $table->dropColumn('lugar_reentrenamiento');
        });
    }
};
