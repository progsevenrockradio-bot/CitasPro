<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de países con prefijos telefónicos internacionales.
 *
 * Usada en el selector de código de país al registrar el teléfono.
 * Los datos se cargan desde PaisesSeeder (estáticos, no cambian).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->smallIncrements('id');

            // Nombres
            $table->string('nombre', 100);          // Nombre en español: "España"
            $table->string('nombre_en', 100);       // Nombre en inglés: "Spain"

            // Códigos ISO
            $table->char('codigo_iso2', 2)->unique(); // ES, US, FR ...
            $table->char('codigo_iso3', 3)->unique(); // ESP, USA, FRA ...

            // Prefijo telefónico (sin +): "34", "1", "44" ...
            // Algunos países comparten prefijo (p.ej. +1 para EE.UU., Canadá, etc.)
            $table->string('prefijo', 10);

            // Emoji de bandera del país (🇪🇸, 🇺🇸 ...)
            $table->string('bandera', 10)->nullable();

            // Región para agrupar en el selector
            $table->enum('region', [
                'europa',
                'america_norte',
                'america_central',
                'america_sur',
                'caribe',
                'africa',
                'asia',
                'oceania',
                'oriente_medio',
            ])->default('europa');

            // Orden preferente (países más usados salen primero)
            $table->unsignedTinyInteger('orden_preferencia')->default(99);

            $table->boolean('activo')->default(true);

            // Índices para búsqueda rápida
            $table->index(['nombre', 'prefijo']);
            $table->index('prefijo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paises');
    }
};
