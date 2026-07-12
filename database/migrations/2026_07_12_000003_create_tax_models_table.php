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
        Schema::create('tax_models', function (Blueprint $table) {
            $table->id();
            
            // Relación con negocio
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            
            // Especificaciones de la declaración
            $table->string('modelo', 5); // 303, 130, 131, 111, 115, 349
            $table->year('ejercicio');   // ej. 2026
            $table->string('periodo', 3); // 1T, 2T, 3T, 4T (Trimestral) o 01-12 (Mensual)
            
            // Datos del resultado
            $table->decimal('resultado', 15, 4)->default(0.0000);
            $table->string('resultado_tipo', 10)->default('ingresar'); // ingresar, devolver, compensar, cero/negativa
            
            // Presentación y AEAT
            $table->string('estado')->default('borrador'); // borrador, presentado
            $table->dateTime('fecha_presentacion')->nullable();
            $table->string('nrc_justificante')->nullable(); // Código NRC o CSV de presentación ante la AEAT
            
            // Desglose dinámico del modelo tributario (casillas de AEAT y sus valores)
            // ej: { "casilla_01": 1500.00, "casilla_03": 315.00, "casilla_27": 315.00, ... }
            $table->json('declaracion_desglose')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Evitar duplicados del mismo modelo para el mismo periodo/ejercicio en un negocio
            $table->unique(['negocio_id', 'modelo', 'ejercicio', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_models');
    }
};
