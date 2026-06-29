<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de citas/reservas (núcleo del sistema).
     * Registra cada turno reservado por un cliente con un profesional.
     */
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_referencia', 20)->unique(); // Ej: CIT-2024-00123
            $table->foreignId('negocio_id')->constrained('negocios')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();

            // Horario
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('duracion_min');

            // Estado del flujo
            $table->enum('estado', [
                'pendiente',      // Recién creada, esperando confirmación
                'confirmada',     // Confirmada por el negocio
                'en_curso',       // En progreso
                'completada',     // Finalizada exitosamente
                'cancelada',      // Cancelada por el cliente
                'no_asistio',     // No-show
                'rechazada',      // Rechazada por el negocio
            ])->default('pendiente');

            // Precios
            $table->decimal('precio_total', 8, 2)->default(0.00);
            $table->string('moneda', 3)->default('EUR');

            // Comunicación
            $table->boolean('recordatorio_enviado')->default(false);
            $table->timestamp('recordatorio_enviado_en')->nullable();

            // Notas
            $table->text('notas_cliente')->nullable();     // Mensaje del cliente al reservar
            $table->text('notas_profesional')->nullable(); // Notas internas del profesional

            // Origen de la reserva
            $table->enum('canal', ['web', 'app', 'whatsapp', 'telefono', 'presencial'])->default('web');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes
            $table->index(['negocio_id', 'fecha', 'estado']);
            $table->index(['profesional_id', 'fecha']);
            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
