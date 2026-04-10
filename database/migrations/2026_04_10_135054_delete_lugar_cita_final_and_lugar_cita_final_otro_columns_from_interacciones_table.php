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
            if (Schema::hasColumn('interacciones', 'lugar_cita_final')) {
                $table->dropColumn('lugar_cita_final');
            }

            if (Schema::hasColumn('interacciones', 'lugar_cita_final_otro')) {
                $table->dropColumn('lugar_cita_final_otro');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            //
        });
    }
};
