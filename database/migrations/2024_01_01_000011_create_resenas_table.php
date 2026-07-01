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
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            
            // La cita a la que pertenece la reseña (opcional si permiten reseñas libres)
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();

            // Calificación de 1 a 5
            $table->unsignedTinyInteger('calificacion')->default(5);
            $table->text('comentario')->nullable();

            $table->boolean('activo')->default(true); // Para poder ocultar reseñas abusivas

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
