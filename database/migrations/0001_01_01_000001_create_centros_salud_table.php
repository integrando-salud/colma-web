<?php

use App\Models\Pais;
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
        Schema::create('centros_salud', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignIdFor(Pais::class, 'pais_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->boolean('activo')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centros_salud', function (Blueprint $table) {
            $table->dropForeign(['pais_id']);
        });

        Schema::dropIfExists('centros_salud');
    }
};
