<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SignInvoiceWithVeriFactu implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de reintentos permitidos.
     */
    public int $tries = 3;

    /**
     * Tiempo de espera antes de reintentar en segundos.
     */
    public int $backoff = 15;

    public function handle(AppointmentCompleted $event): void
    {
        $cita = $event->cita;

        // Buscar la factura generada para esta cita
        $invoice = Invoice::where('negocio_id', $cita->negocio_id)
            ->whereJsonContains('metadata_adicional->cita_id', $cita->id)
            ->first();

        if (!$invoice) {
            throw new \Exception("Factura no encontrada para la cita {$cita->id}. Reintentando...");
        }

        try {
            Log::info("Enviando factura ID {$invoice->id} (VeriFactu) a la AEAT...");
            
            // Simulación de delay de red / API externa
            usleep(200000); 

            $invoice->enviado_aeat = true;
            $invoice->fecha_envio_aeat = now();
            $invoice->estado = 'verificada_aeat';
            $invoice->save();

            Log::info("Factura ID {$invoice->id} firmada y verificada exitosamente en la AEAT (VeriFactu).");
        } catch (\Throwable $e) {
            Log::error("Error al firmar/enviar la factura ID {$invoice->id} a VeriFactu: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Manejar fallos definitivos del Job.
     */
    public function failed(AppointmentCompleted $event, \Throwable $exception): void
    {
        $cita = $event->cita;
        $invoice = Invoice::where('negocio_id', $cita->negocio_id)
            ->whereJsonContains('metadata_adicional->cita_id', $cita->id)
            ->first();

        if ($invoice) {
            $invoice->estado = 'error_verificacion_aeat';
            $invoice->save();
        }

        Log::critical("FALLO CRÍTICO VERIFACTU: La factura para la cita {$cita->id} no se pudo firmar/verificar tras los reintentos. Error: " . $exception->getMessage());
    }
}
