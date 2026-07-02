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
        Schema::create('fichas_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('profesional_id')->constrained('profesionales')->onDelete('cascade');
            $table->foreignId('cita_id')->nullable()->constrained('citas')->onDelete('set null');
            $table->text('motivo_consulta')->nullable(); // Encriptado en eloquent
            $table->text('diagnostico')->nullable(); // Encriptado en eloquent
            $table->text('tratamiento')->nullable(); // Encriptado en eloquent
            $table->text('receta')->nullable(); // Encriptado en eloquent
            $table->text('notas')->nullable(); // Encriptado en eloquent
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichas_clinicas');
    }
};
