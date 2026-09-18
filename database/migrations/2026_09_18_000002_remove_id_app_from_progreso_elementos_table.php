<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La migración inicial llegó a ejecutarse en desarrollo antes de que
        // se simplificara el contrato. En instalaciones nuevas no hay nada que
        // modificar porque la tabla ya nace sin este identificador local.
        if (! Schema::hasColumn('progreso_elementos', 'id_app')) {
            return;
        }

        Schema::table('progreso_elementos', function (Blueprint $table) {
            $table->dropUnique('progreso_elemento_app_unico');
            $table->dropColumn('id_app');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('progreso_elementos', 'id_app')) {
            return;
        }

        Schema::table('progreso_elementos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_app')->nullable()->after('id');
            $table->unique(['paciente_id', 'id_app'], 'progreso_elemento_app_unico');
        });
    }
};
