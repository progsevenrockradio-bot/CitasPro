<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de negocios (multi-rubro).
     * Un negocio pertenece a una categoría y puede tener múltiples profesionales.
     */
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('slug', 150)->unique();
            $table->string('descripcion')->nullable();
            $table->string('logo')->nullable();            // Ruta del logo
            $table->string('cover_imagen')->nullable();    // Imagen de portada

            // Información de contacto
            $table->string('telefono', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('sitio_web')->nullable();

            // Dirección
            $table->string('direccion')->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('pais', 100)->default('ES');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();

            // Configuración de agenda
            $table->json('horario_apertura')->nullable();  // {"lun": {"inicio":"09:00","fin":"18:00"}, ...}
            $table->integer('duracion_turno_min')->default(30); // Duración default de turnos en minutos
            $table->integer('anticipacion_min_reserva')->default(60); // Mínimo de anticipación para reservar
            $table->integer('cancelacion_limite_horas')->default(24); // Horas límite para cancelar

            // Plan SaaS
            $table->enum('plan', ['free', 'basic', 'pro', 'enterprise'])->default('free');
            $table->timestamp('plan_vence_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('verificado')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
