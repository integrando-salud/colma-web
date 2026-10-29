<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_api_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->timestamp('received_at')->index();
            $table->string('endpoint')->index();
            $table->string('method', 10);
            // Sin FK: conservar evidencia aunque se elimine el usuario.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->json('client_metadata')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_api_logs');
    }
};
