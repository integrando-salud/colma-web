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

        $hasLegacySession = Schema::hasColumn('tratamiento_multimedia', 'tratamiento_sesion_id');
        $foreignKeys = collect(Schema::getForeignKeys('tratamiento_multimedia'));
        $indexes = collect(Schema::getIndexes('tratamiento_multimedia'));

        Schema::table('tratamiento_multimedia', function (Blueprint $table) use ($hasLegacySession, $foreignKeys, $indexes) {
            if ($hasLegacySession) {
                $foreignKey = $foreignKeys->first(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['tratamiento_sesion_id']
                );

                if ($foreignKey) {
                    $table->dropForeign($foreignKey['name']);
                }

                if ($indexes->contains('name', 'tratamiento_multimedia_ruta_unica')) {
                    $table->dropUnique('tratamiento_multimedia_ruta_unica');
                }

                $table->dropColumn('tratamiento_sesion_id');
            }

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
