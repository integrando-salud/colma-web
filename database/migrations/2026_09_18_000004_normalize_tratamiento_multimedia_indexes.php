<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige de forma segura el índice creado por una versión inicial de la
     * limpieza. Una ruta puede aparecer en versiones diferentes del catálogo;
     * su unicidad física se valida dentro de cada importación, no entre versiones.
     */
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('tratamiento_multimedia'))->pluck('name');

        Schema::table('tratamiento_multimedia', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('tratamiento_multimedia_ruta_unica')) {
                $table->dropUnique('tratamiento_multimedia_ruta_unica');
            }

            if (! $indexes->contains('tratamiento_multimedia_elemento_ruta_unica')) {
                $table->unique(['tratamiento_elemento_id', 'ruta'], 'tratamiento_multimedia_elemento_ruta_unica');
            }
        });
    }

    public function down(): void
    {
        // Normalización irreversible de una migración destructiva previa.
    }
};
