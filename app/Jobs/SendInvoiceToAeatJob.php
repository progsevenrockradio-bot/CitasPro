<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SendInvoiceToAeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $invoiceId;

    /**
     * Número máximo de intentos antes de marcar el trabajo como fallido.
     * En caso de fallo definitivo pasará a la tabla failed_jobs y requerirá atención manual.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Calcula los segundos a esperar antes de reintentar el trabajo (Backoff exponencial).
     * Tiempos: 1 minuto, 5 minutos, 30 minutos, 1 hora
     *
     * @return array
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 3600];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = Invoice::find($this->invoiceId);

        if (!$invoice) {
            Log::error("SendInvoiceToAeatJob falló: La factura {$this->invoiceId} no existe.");
            return;
        }

        if ($invoice->enviado_aeat) {
            return; // Ya fue enviada previamente
        }

        try {
            // Simulamos la llamada a la API de VeriFactu (AEAT).
            // En producción, se usarían los endpoints reales (Soap/REST) y certificados electrónicos.
            // Timeout de 10s para evitar que los workers se queden atascados eternamente.
            $response = Http::timeout(10)->post('https://prewww1.aeat.es/wlpl/invoices/submit', [
                'hash'  => $invoice->hash,
                'total' => $invoice->total,
                'tipo'  => $invoice->tipo_factura,
            ]);

            // Comportamiento ante errores temporales de red/servidor (500, 502, 503, 504)
            if ($response->serverError()) {
                throw new Exception('Servicio AEAT temporalmente no disponible (HTTP ' . $response->status() . ').');
            }

            if ($response->successful()) {
                $invoice->update([
                    'enviado_aeat' => true,
                    'estado'       => 'verificada_aeat',
                ]);
                Log::info("Factura {$invoice->id} remitida a la AEAT con éxito.");
            } else {
                // Errores de cliente (4xx). Ej: Certificado no válido, XML mal formado, datos faltantes.
                // Estos errores no se solucionan reintentando, así que registramos el error y abortamos el retry.
                Log::error("Rechazo de AEAT en factura {$invoice->id}: " . $response->body());
                
                $meta = $invoice->metadata_adicional ?? [];
                $meta['aeat_error_response'] = $response->body();
                
                $invoice->update([
                    'estado'             => 'error_aeat',
                    'metadata_adicional' => $meta
                ]);
            }
        } catch (Exception $e) {
            // Un timeout de Guzzle o la excepción lanzada arriba caerán aquí.
            Log::warning("Fallo temporal de conexión con AEAT para factura {$invoice->id}. Se aplicará backoff. Error: " . $e->getMessage());
            
            // Relanzar la excepción indica a Laravel que el Job ha fallado y debe consumir un intento (y aplicar backoff).
            throw $e;
        }
    }
}
