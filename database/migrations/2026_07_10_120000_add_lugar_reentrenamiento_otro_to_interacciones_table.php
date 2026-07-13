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
            $table->string('lugar_reentrenamiento_otro')->nullable()->after('lugar_reentrenamiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->dropColumn('lugar_reentrenamiento_otro');
        });
    }
};
