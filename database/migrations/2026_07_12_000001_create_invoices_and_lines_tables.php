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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Relación con negocio
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            
            // Garantiza integridad lógica y referencial con negocio_datos_fiscales
            $table->foreignId('negocio_datos_fiscales_id')
                ->nullable()
                ->constrained('negocio_datos_fiscales')
                ->nullOnDelete();

            // Relación con el cliente al que se factura
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();

            // Identificación y numeración de factura
            $table->string('serie');
            $table->string('numero');
            $table->dateTime('fecha_emision');
            $table->string('tipo_factura')->default('B2C'); // B2B, B2C, ROI, EXT (Extracomunitario)
            $table->string('estado')->default('emitida'); // borrador, emitida, anulada, rectificativa
            
            // Soporte Multi-Moneda
            $table->string('moneda', 3)->default('EUR');
            $table->decimal('tipo_cambio', 15, 6)->default(1.000000);
            
            // Desglose financiero general
            $table->decimal('subtotal', 15, 4)->default(0.0000);
            $table->decimal('impuestos', 15, 4)->default(0.0000);
            $table->decimal('total', 15, 4)->default(0.0000);
            
            // VeriFactu & Criptografía
            $table->string('hash_anterior', 256)->nullable(); // Hash encadenado de la factura previa
            $table->string('hash_actual', 256)->nullable();   // Hash generado para esta factura
            $table->text('firma')->nullable();                // Firma criptográfica
            $table->text('datos_qr')->nullable();             // Contenido/URL del código QR reglamentario
            $table->boolean('enviado_aeat')->default(false);
            $table->dateTime('fecha_envio_aeat')->nullable();

            // JSON flexible para datos dinámicos (ej: datos del cliente, método pago, etc.)
            $table->json('datos_cliente_snapshot')->nullable();
            $table->json('metadata_adicional')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Índices para búsquedas eficientes y control de unicidad por serie/número por negocio
            $table->unique(['negocio_id', 'serie', 'numero']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 12, 4)->default(1.0000);
            $table->decimal('precio_unitario', 15, 4)->default(0.0000);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0.00); // ej. 10.00 para 10%
            
            // Impuestos específicos por línea
            $table->decimal('iva_porcentaje', 5, 2)->default(21.00);      // ej. 21.00
            $table->decimal('irpf_porcentaje', 5, 2)->default(0.00);     // ej. 15.00
            
            // Totales de línea calculados
            $table->decimal('subtotal', 15, 4)->default(0.0000);
            $table->decimal('impuestos', 15, 4)->default(0.0000);
            $table->decimal('total', 15, 4)->default(0.0000);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
