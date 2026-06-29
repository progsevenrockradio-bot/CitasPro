<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos de integración con Telegram a la tabla de profesionales.
     * telegram_chat_id: el ID de chat del profesional en el bot de CitasPro.
     * Para obtenerlo: el profesional envía /start al bot y se captura via webhook.
     */
    public function up(): void
    {
        Schema::table('profesionales', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('aceptar_online')
                ->comment('Chat ID de Telegram del profesional para notificaciones del bot');
            $table->boolean('notificaciones_telegram')->default(false)->after('telegram_chat_id')
                ->comment('Tiene activadas las notificaciones por Telegram');
            $table->boolean('notificaciones_whatsapp')->default(true)->after('notificaciones_telegram')
                ->comment('Tiene activadas las notificaciones por WhatsApp (staff)');
        });
    }

    public function down(): void
    {
        Schema::table('profesionales', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'notificaciones_telegram', 'notificaciones_whatsapp']);
        });
    }
};
