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
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            // Referenciamos a paises (id: smallIncrements en paises table, así que usaremos smallInteger)
            // Revisando create_paises_table: $table->smallIncrements('id');
            // Entonces foreignId no nos sirve directo si asume unsignedBigInteger, pero Laravel 10/11 puede ser inteligente,
            // O mejor lo forzamos a unsignedSmallInteger.
            $table->unsignedSmallInteger('pais_id');
            $table->foreign('pais_id')->references('id')->on('paises')->cascadeOnDelete();
            
            $table->string('nombre');
            $table->string('codigo', 10)->nullable(); // Ej: AN, TX, FL
            $table->timestamps();

            $table->index('pais_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
};
