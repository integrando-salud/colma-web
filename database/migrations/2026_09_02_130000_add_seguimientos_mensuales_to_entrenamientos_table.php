<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrenamientos', function (Blueprint $table) {
            // El default conserva el circuito de cinco seguimientos para los
            // entrenamientos que ya existían antes del piloto.
            $table->unsignedTinyInteger('seguimientos_mensuales')->default(5)->after('sesiones');
        });
    }

    public function down(): void
    {
        Schema::table('entrenamientos', function (Blueprint $table) {
            $table->dropColumn('seguimientos_mensuales');
        });
    }
};
