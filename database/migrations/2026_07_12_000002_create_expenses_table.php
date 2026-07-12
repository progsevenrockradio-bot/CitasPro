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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            
            // Relación con negocio
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            
            // Proveedor / Acreedor (opcional, estructurado)
            $table->string('proveedor_nombre')->nullable();
            $table->string('proveedor_nif')->nullable();

            // Detalles del gasto
            $table->string('concepto');
            $table->dateTime('fecha_gasto');
            $table->string('categoria'); // explotacion, inversion, home_office, dietas, etc.
            
            // Desglose financiero
            $table->decimal('subtotal', 15, 4)->default(0.0000);
            $table->decimal('iva_porcentaje', 5, 2)->default(21.00);
            $table->decimal('impuestos', 15, 4)->default(0.0000);
            $table->decimal('total', 15, 4)->default(0.0000);
            
            // Deducción y Afectación
            $table->decimal('afectacion_porcentaje', 5, 2)->default(100.00); // Para Home Office u otros gastos con deducción parcial (ej: 30%)
            $table->decimal('importe_deducible', 15, 4)->default(0.0000);    // total * afectacion_porcentaje

            // Amortización (Para bienes de inversión > 300€)
            $table->boolean('es_bien_inversion')->default(false);
            
            // Campos de archivos adjuntos (Facturas de proveedores, recibos, etc.)
            $table->string('documento_adjunto_path')->nullable();

            // JSON dinámico para detalles adicionales según la categoría:
            // - dietas: { comensales: [], establecimiento: "", motivo: "" }
            // - home_office: { metros_totales: 100, metros_afectos: 15, porcentaje_afectacion_adicional: 30 }
            // - inversion: { vida_util_anios: 5, porcentaje_amortizacion_anual: 20 }
            $table->json('detalles_categoria')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
