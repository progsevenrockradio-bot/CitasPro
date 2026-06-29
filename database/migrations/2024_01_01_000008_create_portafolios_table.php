<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de portafolio de trabajos del profesional.
     * Galería de imágenes/trabajos para mostrar al cliente.
     */
    public function up(): void
    {
        Schema::create('portafolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->string('titulo', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen');                      // Ruta de la imagen
            $table->string('imagen_miniatura')->nullable();
            $table->enum('tipo', ['imagen', 'video', 'antes_despues'])->default('imagen');
            $table->string('imagen_antes')->nullable();    // Para tipo antes_despues
            $table->boolean('destacado')->default(false);  // Aparece primero
            $table->boolean('publico')->default(true);     // Visible en perfil público
            $table->integer('orden')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portafolios');
    }
};
