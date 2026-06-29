<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivot: qué servicios puede ofrecer cada profesional.
     * Un profesional puede tener distintos precios/duraciones por servicio.
     */
    public function up(): void
    {
        Schema::create('profesional_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->decimal('precio_override', 8, 2)->nullable(); // Precio personal diferente al del servicio
            $table->integer('duracion_override_min')->nullable();  // Duración personalizada
            $table->boolean('activo')->default(true);

            $table->unique(['profesional_id', 'servicio_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional_servicio');
    }
};
