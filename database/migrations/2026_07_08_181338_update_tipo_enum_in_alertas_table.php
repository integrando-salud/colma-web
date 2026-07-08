<?php

use App\Models\Alerta;
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

        Schema::table('alertas', function (Blueprint $table) {
            $table->enum('tipo', Alerta::TIPOS_ALERTA)->change();
        });

    }

    public function down(): void
    {
        // do nothing: as the original create_alertas_table migration script also uses Alerta::TIPOS_ALERTA rather
        // than hard coded values, we cannot reliably revert as the original state (with the extra 'calls' type or not
        // depends on when the database was created.
    }

};
