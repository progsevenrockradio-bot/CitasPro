<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de categorías de sectores de negocio.
     * Ejemplos: Peluquería, Estética, Medicina, Educación, etc.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);                 // Nombre del sector
            $table->string('slug', 100)->unique();         // URL friendly
            $table->string('descripcion')->nullable();     // Descripción breve
            $table->string('icono', 100)->nullable();      // Clase CSS o emoji
            $table->string('color_hex', 7)->default('#6366f1'); // Color representativo
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
