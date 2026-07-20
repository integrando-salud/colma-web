<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pacientes', 'paquete_id')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropForeign(['paquete_id']);
                $table->dropColumn('paquete_id');
            });
        }

        Schema::dropIfExists('paquetes');
    }

    public function down(): void
    {
    }
};
