<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de servicios ofrecidos por el negocio.
     * Multi-rubro: corte de pelo, consulta médica, clase de inglés, etc.
     */
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->integer('duracion_min')->default(30);  // Duración en minutos
            $table->decimal('precio', 8, 2)->default(0.00);
            $table->string('moneda', 3)->default('EUR');
            $table->boolean('precio_desde')->default(false); // "Desde €XX"
            $table->integer('max_clientes_simultaneous')->default(1); // Para clases grupales
            $table->string('categoria_servicio', 100)->nullable(); // Sub-categoría interna
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);          // Para ordenar en UI

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
