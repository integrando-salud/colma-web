<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El catálogo y el progreso ya se representan mediante elementos lógicos.
     * Esta limpieza es irreversible: el entorno aún no contiene datos productivos
     * que requieran reconstruir las estructuras descartadas.
     */
    public function up(): void
    {
        Schema::dropIfExists('multimedia_read');
        Schema::dropIfExists('cuestionarios_preguntas');
        Schema::dropIfExists('cuestionarios');

        Schema::table('tratamiento_multimedia', function (Blueprint $table) {
            // Las claves foráneas existentes usan los índices compuestos
            // transitorios. Se crean índices simples antes de eliminarlos.
            $table->index('tratamiento_sesion_id');
            $table->dropForeign(['tratamiento_sesion_id']);
            $table->dropUnique('tratamiento_multimedia_ruta_unica');
            $table->dropIndex(['tratamiento_sesion_id']);
            $table->dropColumn('tratamiento_sesion_id');
            $table->foreignId('tratamiento_elemento_id')->nullable(false)->change();
        });

        Schema::table('entrenamientos', function (Blueprint $table) {
            $table->dropColumn('sesiones');
        });
    }

    public function down(): void
    {
        // Limpieza destructiva intencionalmente irreversible.
    }
};
