<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow goals without a specified place, as accepted by the synchronization request.
     */
    public function up(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->text('lugar')->nullable()->change();
        });
    }

    /**
     * Restore the original schema when no records with a null place exist.
     */
    public function down(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->text('lugar')->nullable(false)->change();
        });
    }
};
