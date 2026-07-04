<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la columna 'email' a otp_codes para soportar
     * el envío del OTP por correo electrónico (además de por teléfono).
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            // Email del destinatario (nullable — se puede usar teléfono O email)
            $table->string('email')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
