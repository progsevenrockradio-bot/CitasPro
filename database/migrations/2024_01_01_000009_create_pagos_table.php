<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de pagos asociados a las citas.
     * Registro de cobros con soporte para múltiples métodos.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('negocio_id')->constrained('negocios')->restrictOnDelete();

            $table->string('referencia_externa')->nullable(); // ID del gateway de pago
            $table->decimal('monto', 8, 2);
            $table->decimal('descuento', 8, 2)->default(0.00);
            $table->decimal('impuesto', 8, 2)->default(0.00);
            $table->decimal('monto_total', 8, 2);           // monto - descuento + impuesto
            $table->string('moneda', 3)->default('EUR');

            $table->enum('metodo', [
                'efectivo',
                'tarjeta',
                'transferencia',
                'stripe',
                'paypal',
                'bizum',
                'otro',
            ])->default('efectivo');

            $table->enum('estado', [
                'pendiente',
                'procesando',
                'completado',
                'fallido',
                'reembolsado',
                'parcialmente_reembolsado',
            ])->default('pendiente');

            $table->text('notas')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->json('metadata')->nullable(); // Respuesta raw del gateway

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
