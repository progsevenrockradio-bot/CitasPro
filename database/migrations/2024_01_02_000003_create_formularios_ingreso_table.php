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
        Schema::create('formularios_ingreso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->onDelete('cascade');
            $table->text('antecedentes_medicos')->nullable(); // Encriptado en eloquent
            $table->text('antecedentes_familiares')->nullable(); // Encriptado en eloquent
            $table->text('alergias')->nullable(); // Encriptado en eloquent
            $table->text('medicacion_actual')->nullable(); // Encriptado en eloquent
            $table->string('tipo_sangre', 5)->nullable();
            $table->boolean('firmado_consentimiento')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formularios_ingreso');
    }
};
