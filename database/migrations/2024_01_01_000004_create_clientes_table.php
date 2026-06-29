<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de clientes finales.
     * Se autentican via OTP (sin contraseña), identificados por número de celular.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('telefono', 20)->unique();       // Identificador primario (login OTP)
            $table->string('email', 100)->nullable()->unique();
            $table->string('foto')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['masculino', 'femenino', 'otro', 'prefiero_no_decir'])->nullable();
            $table->string('pais', 10)->default('ES');
            $table->text('notas_internas')->nullable();     // Notas privadas del negocio sobre el cliente
            $table->boolean('activo')->default(true);
            $table->boolean('acepta_marketing')->default(false); // GDPR
            $table->timestamp('telefono_verificado_en')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
