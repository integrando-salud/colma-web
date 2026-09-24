<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tratamiento_versiones', function (Blueprint $table) {
            $table->index(['pais', 'tipo', 'created_at', 'id'], 'tratamiento_versiones_ultima_version_index');
        });
    }

    public function down(): void
    {
        Schema::table('tratamiento_versiones', function (Blueprint $table) {
            $table->dropIndex('tratamiento_versiones_ultima_version_index');
        });
    }
};
