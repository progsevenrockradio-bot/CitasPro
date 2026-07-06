<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_historia_clinica', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // "Historia Clínica Médica", "Historia Clínica Dental"
            $table->string('tipo');   // 'medical', 'dental'
            $table->json('campos');   // Definición de campos del formulario
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_historia_clinica');
    }
};
