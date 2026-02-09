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
        Schema::table('glucosa', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('glucosa', 'id_app')) {
                $table->integer('id_app')->after('id')->nullable();
            }

            if (!Schema::hasColumn('glucosa', 'advertencia')) {
                $table->enum('advertencia', ['Baja', 'Normal', 'Alta'])->after('ayuno')->nullable();
            }

            // Rename column 'valor' to 'valor_mgdl' if needed
            if (Schema::hasColumn('glucosa', 'valor') && !Schema::hasColumn('glucosa', 'valor_mgdl')) {
                $table->renameColumn('valor', 'valor_mgdl');
            }

            if (!Schema::hasColumn('glucosa', 'fecha_usuario')) {
                $table->timestamp('fecha_usuario')->after('fecha_registro');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glucosa', function (Blueprint $table) {
            // Drop columns added in up()
            if (Schema::hasColumn('glucosa', 'id_app')) {
                $table->dropColumn('id_app');
            }

            if (Schema::hasColumn('glucosa', 'advertencia')) {
                $table->dropColumn('advertencia');
            }

            // Rename back if needed
            if (Schema::hasColumn('glucosa', 'valor_mgdl') && !Schema::hasColumn('glucosa', 'valor')) {
                $table->renameColumn('valor_mgdl', 'valor');
            }

            if (Schema::hasColumn('glucosa', 'fecha_usuario')) {
                $table->dropColumn('fecha_usuario');
            }
        });
    }
};
