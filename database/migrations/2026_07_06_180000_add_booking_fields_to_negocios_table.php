<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos de configuración de reserva online al negocio.
     */
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            // ¿Está activo el enlace de reserva público?
            $table->boolean('booking_activo')->default(true)->after('verificado');
            // Mensaje personalizado que ve el cliente al abrir el enlace
            $table->text('booking_mensaje')->nullable()->after('booking_activo');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['booking_activo', 'booking_mensaje']);
        });
    }
};
