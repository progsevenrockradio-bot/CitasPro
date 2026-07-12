<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInvoiceToClient implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de reintentos permitidos.
     */
    public int $tries = 3;

    public function handle(AppointmentCompleted $event): void
    {
        $cita = $event->cita;

        $invoice = Invoice::where('negocio_id', $cita->negocio_id)
            ->whereJsonContains('metadata_adicional->cita_id', $cita->id)
            ->first();

        if (!$invoice) {
            throw new \Exception("Factura no encontrada para enviar al cliente. Reintentando...");
        }

        $cliente = $cita->cliente;
        if (!$cliente || empty($cliente->email)) {
            Log::warning("No se pudo enviar la factura ID {$invoice->id} porque el cliente no tiene un email válido.");
            return;
        }

        // En entornos reales se usaría un Mailer / Mailable adjuntando el PDF generado
        Log::info("Enviando correo electrónico con la factura ID {$invoice->id} al cliente {$cliente->email}...");
        
        // Simulación de retraso de envío
        usleep(100000);

        Log::info("Correo de factura {$invoice->serie}-{$invoice->numero} enviado exitosamente a {$cliente->email}.");
    }
}
