<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote que asocia clientes a negocios.
     * 
     * Un cliente (identificado por su teléfono único) puede reservar en
     * múltiples negocios de CitasPro. Esta tabla aísla los datos por negocio:
     * cada negocio solo ve los clientes que reservaron con él.
     */
    public function up(): void
    {
        Schema::create('negocio_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')
                ->constrained('negocios')
                ->cascadeOnDelete();
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();
            // Notas que el negocio tiene sobre este cliente específico
            $table->text('notas_negocio')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Un cliente solo puede estar una vez por negocio
            $table->unique(['negocio_id', 'cliente_id']);

            $table->index('negocio_id');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocio_cliente');
    }
};
