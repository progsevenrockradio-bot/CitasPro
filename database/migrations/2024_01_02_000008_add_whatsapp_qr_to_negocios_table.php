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
        Schema::table('negocios', function (Blueprint $table) {
            $table->string('whatsapp_modelo', 20)->default('meta'); // 'meta' o 'qr'
            $table->string('whatsapp_session_instance', 100)->nullable(); // Nombre de instancia en el Gateway
            $table->string('whatsapp_session_token', 100)->nullable();    // Token de acceso específico a la instancia
            $table->string('whatsapp_qr_status', 30)->default('desconectado'); // 'desconectado', 'escaneando', 'conectado'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_modelo', 'whatsapp_session_instance', 'whatsapp_session_token', 'whatsapp_qr_status']);
        });
    }
};
