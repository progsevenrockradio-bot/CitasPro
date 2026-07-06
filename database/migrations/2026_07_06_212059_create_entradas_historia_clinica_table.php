<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entradas_historia_clinica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('plantilla_id')->constrained('plantillas_historia_clinica')->cascadeOnDelete();
            $table->unsignedBigInteger('cita_id')->nullable(); // FK nullable sin constraint estricto
            $table->json('respuestas');
            $table->timestamps();
            $table->softDeletes();

            $table->index('negocio_id');
            $table->index('cliente_id');
            $table->index('cita_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas_historia_clinica');
    }
};
