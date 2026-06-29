<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de códigos OTP para autenticación sin contraseña.
     * Almacena los códigos de 6 dígitos enviados por WhatsApp/SMS.
     */
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->index();       // Número que solicitó el OTP
            $table->string('codigo', 6);                   // Código de 6 dígitos
            $table->string('tipo', 20)->default('login');  // login | registro | verificacion
            $table->boolean('usado')->default(false);
            $table->integer('intentos')->default(0);       // Intentos fallidos
            $table->ipAddress('ip_solicitante')->nullable();
            $table->timestamp('expira_en');                // Normalmente +10 minutos
            $table->timestamp('usado_en')->nullable();

            $table->timestamps();

            // Índice para búsquedas frecuentes
            $table->index(['telefono', 'codigo', 'usado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
