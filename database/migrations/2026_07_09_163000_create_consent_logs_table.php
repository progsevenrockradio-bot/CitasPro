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
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable(); // 'profesional' o 'cliente'
            $table->string('document_type'); // 'aviso_legal', 'politica_privacidad', 'dpa', etc.
            $table->string('document_version'); // ej. '1.0'
            $table->string('document_hash', 64); // SHA-256 del contenido
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamp('accepted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
