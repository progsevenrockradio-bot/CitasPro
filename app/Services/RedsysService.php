<?php

namespace App\Services;

use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class RedsysService
{
    /**
     * Devuelve los parámetros cifrados y la URL para el envío al formulario de Redsys / Bizum.
     * Si no hay credenciales configuradas, simula el flujo.
     */
    public function obtenerParametrosFormulario(Pago $pago, bool $esBizum = false): array
    {
        $negocio = $pago->negocio;
        
        $merchantCode = $negocio->redsys_merchant_code ?: config('services.redsys.merchant_code');
        $terminal     = $negocio->redsys_terminal ?: config('services.redsys.terminal', '1');
        $secretKey    = $negocio->redsys_secret_key ?: config('services.redsys.secret_key');

        if (empty($secretKey) || $secretKey === 'Coloca_Aqui_Tu_Redsys_Key') {
            // Modo simulación en local
            Log::info("Redsys: Generada transacción simulada para el pago #{$pago->id}");
            return [
                'simulado' => true,
                'url'      => env('APP_URL', 'https://jmfn8n.top') . '/api/pagos/confirmar-simulado?cita_id=' . $pago->cita_id . '&telefono=' . urlencode($pago->cliente->telefono),
            ];
        }

        // El monto en Redsys va en centavos sin decimales (ej: 10.50 EUR -> 1050)
        $monto = (int) round($pago->monto * 100);
        $order = str_pad($pago->id, 12, '0', STR_PAD_LEFT); // Obligatorio de 12 dígitos

        $params = [
            'DS_MERCHANT_AMOUNT'         => (string) $monto,
            'DS_MERCHANT_ORDER'          => $order,
            'DS_MERCHANT_MERCHANTCODE'   => $merchantCode,
            'DS_MERCHANT_CURRENCY'       => '978', // 978 = EUR
            'DS_MERCHANT_TRANSACTIONTYPE'=> '0',
            'DS_MERCHANT_TERMINAL'       => $terminal,
            'DS_MERCHANT_MERCHANTURL'    => env('APP_URL', 'https://jmfn8n.top') . '/api/pagos/webhook/redsys',
            'DS_MERCHANT_URLOK'          => env('APP_URL', 'https://jmfn8n.top') . '/dashboard?redsys_success=1',
            'DS_MERCHANT_URLKO'          => env('APP_URL', 'https://jmfn8n.top') . '/dashboard?redsys_fail=1',
        ];

        // Activar Bizum en Redsys si es necesario
        if ($esBizum) {
            $params['DS_MERCHANT_PAYMETHODS'] = 'z'; // 'z' es el identificador de Bizum
        }

        try {
            $jsonParams = base64_encode(json_encode($params));
            
            // Firma SHA256 con cifrado 3DES utilizando la clave secreta y el Order ID
            $key = base64_decode($secretKey);
            $key = $this->encrypt3DES($order, $key);
            $signature = base64_encode(hash_hmac('sha256', $jsonParams, $key, true));

            return [
                'simulado'          => false,
                'url'               => 'https://sis-t.redsys.es:25443/sis/realizarPago', // Sandbox/Test URL
                'params'            => $jsonParams,
                'signature'         => $signature,
                'signature_version' => 'HMAC_SHA256_V1',
            ];
        } catch (\Exception $e) {
            Log::error('Redsys signature error: ' . $e->getMessage());
            return [
                'simulado' => true,
                'url'      => env('APP_URL', 'https://jmfn8n.top') . '/api/pagos/confirmar-simulado?cita_id=' . $pago->cita_id . '&telefono=' . urlencode($pago->cliente->telefono),
            ];
        }
    }

    /**
     * Encriptación 3DES necesaria para las firmas de Redsys.
     */
    private function encrypt3DES(string $data, string $key): string
    {
        $l = ceil(strlen($data) / 8) * 8;
        $data = str_pad($data, $l, "\0");
        return openssl_encrypt($data, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, "\0\0\0\0\0\0\0\0");
    }
}
