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
            if (Schema::hasColumn('interacciones', 'seguimiento_pregunta_f')) {
                $table->dropColumn('seguimiento_pregunta_f');
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
