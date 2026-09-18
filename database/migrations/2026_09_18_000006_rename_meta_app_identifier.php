<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('metas', 'id_app')) {
            Schema::table('metas', function ($table) {
                $table->renameColumn('id_app', 'id_meta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('metas', 'id_meta')) {
            Schema::table('metas', function ($table) {
                $table->renameColumn('id_meta', 'id_app');
            });
        }
    }
};
