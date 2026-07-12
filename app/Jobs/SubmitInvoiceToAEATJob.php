<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\DigitalCertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SubmitInvoiceToAEATJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $invoiceId;

    /**
     * Número máximo de intentos antes de marcar el trabajo como fallido.
     *
     * @var int
     */
    public int $tries = 5;

    /**
     * Calcula los segundos a esperar antes de reintentar el trabajo (Backoff exponencial).
     *
     * @return array<int>
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
    public function handle(DigitalCertificateService $certificateService): void
    {
        $invoice = Invoice::with('negocio.digitalCertificate')->find($this->invoiceId);

        if (!$invoice) {
            Log::error("SubmitInvoiceToAEATJob falló: La factura {$this->invoiceId} no existe.");
            return;
        }

        if ($invoice->enviado_aeat) {
            return; // Ya se envió previamente
        }

        $negocio = $invoice->negocio;
        $certificateModel = $negocio->digitalCertificate;

        if (!$certificateModel) {
            Log::error("SubmitInvoiceToAEATJob abortado: El negocio {$negocio->id} no tiene configurado un certificado digital.");
            
            $meta = $invoice->metadata_adicional ?? [];
            $meta['aeat_submission_error'] = 'No se encontró un certificado digital para este negocio.';
            $invoice->update([
                'estado' => 'error_aeat',
                'metadata_adicional' => $meta,
            ]);
            return;
        }

        try {
            // 1. Descifrar el certificado y contraseña del almacén criptográfico
            $decrypted = $certificateService->getDecryptedCertificate($certificateModel);
            $p12Binary = base64_decode($decrypted['certificate']);
            $p12Password = $decrypted['password'];

            // 2. Simular o realizar la comunicación SOAP/REST con la AEAT
            // En entorno real, usaríamos curl con las opciones:
            // curl_setopt($ch, CURLOPT_SSLCERT, $tempCertPath);
            // curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $p12Password);
            // Para la demostración y testeo, simulamos el consumo del endpoint VeriFactu
            $payload = [
                'nif_emisor' => $invoice->negocioDatosFiscales?->datos_fiscales['nif'] ?? '00000000T',
                'serie' => $invoice->serie,
                'numero' => $invoice->numero,
                'fecha_emision' => $invoice->fecha_emision->format('Y-m-d'),
                'total' => $invoice->total,
                'hash_actual' => $invoice->hash_actual,
                'hash_anterior' => $invoice->hash_anterior,
                'firma_xml' => $invoice->firma,
                'certificado_cn' => $certificateModel->common_name,
            ];

            // Realizamos la petición HTTP simulada
            $response = Http::timeout(10)->post('https://prewww1.aeat.es/wlpl/invoices/verifactu', $payload);

            // Si hay un error del servidor (5xx) o mantenimiento temporal, lanzamos excepción para reintentar
            if ($response->serverError() || $response->status() === 503) {
                throw new Exception("Error temporal del servidor de la AEAT (HTTP {$response->status()}).");
            }

            if ($response->successful()) {
                $invoice->update([
                    'enviado_aeat' => true,
                    'fecha_envio_aeat' => now(),
                    'estado' => 'verificada_aeat',
                ]);
                Log::info("Factura {$invoice->id} enviada y validada por AEAT usando el certificado del negocio {$negocio->id}.");
            } else {
                // Errores de cliente (4xx) - errores en datos fiscales, formato, etc.
                // No reintentamos porque el resultado siempre será el mismo.
                Log::error("AEAT rechazó la factura {$invoice->id}: " . $response->body());
                
                $meta = $invoice->metadata_adicional ?? [];
                $meta['aeat_response_error'] = $response->json() ?? $response->body();
                $invoice->update([
                    'estado' => 'error_aeat',
                    'metadata_adicional' => $meta,
                ]);
            }
        } catch (Exception $e) {
            Log::warning("Fallo en el envío a la AEAT para la factura {$invoice->id}. Reintentando según backoff. Error: " . $e->getMessage());
            
            // Relanzar la excepción para que la cola procese los reintentos
            throw $e;
        }
    }
}
