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
        Schema::table('estado_animo', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_animo', 'id_app')) {
                $table->integer('id_app')->after('id')->nullable();
            }

            if (!Schema::hasColumn('estado_animo', 'fecha_usuario')) {
                $table->timestamp('fecha_usuario')->after('fecha_registro');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estado_animo', function (Blueprint $table) {
            if (Schema::hasColumn('estado_animo', 'id_app')) {
                $table->dropColumn('id_app');
            }

            if (Schema::hasColumn('estado_animo', 'fecha_usuario')) {
                $table->dropColumn('fecha_usuario');
            }
        });
    }
};
