<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de profesionales (staff del negocio).
     * Ejemplos: Médico, Barbero, Profesor, Esteticista, etc.
     */
    public function up(): void
    {
        Schema::create('profesionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('telefono', 20)->nullable()->unique();
            $table->string('email', 100)->nullable()->unique();
            $table->string('foto')->nullable();
            $table->string('titulo', 150)->nullable();     // "Dr.", "Maestro", "Estilista Senior"
            $table->text('bio')->nullable();
            $table->integer('experiencia_anios')->default(0);
            $table->decimal('calificacion_promedio', 3, 2)->default(0.00);
            $table->integer('total_resenas')->default(0);

            // Horarios propios del profesional (puede diferir del negocio)
            $table->json('horario_disponible')->nullable();

            // Rol dentro del negocio
            $table->enum('rol', ['dueño', 'admin', 'profesional'])->default('profesional');

            $table->boolean('activo')->default(true);
            $table->boolean('aceptar_online')->default(true); // Acepta reservas online

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};
