<?php

namespace App\Services;

use App\Models\Pago;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    /**
     * Crea una preferencia de pago en MercadoPago.
     * Si no hay credenciales configuradas, simula el flujo de redirección.
     */
    public function crearPreferencia(Pago $pago): ?string
    {
        $negocio = $pago->negocio;
        
        // Buscar el token específico del negocio o el global del archivo .env
        $token = $negocio->mp_access_token ?: config('services.mercadopago.access_token');

        if (empty($token) || $token === 'Coloca_Aqui_Tu_MercadoPago_Access_Token') {
            // Modo simulación en local
            $mockPreferenceId = 'mp_pref_' . uniqid();
            $pago->update([
                'referencia_externa' => $mockPreferenceId,
                'metadata' => array_merge($pago->metadata ?? [], ['simulado' => true])
            ]);
            Log::info("MercadoPago: Generada preferencia simulada {$mockPreferenceId} para el pago #{$pago->id}");
            
            // Retorna URL de confirmación de simulado del local
            return config('app.url') . '/api/pagos/confirmar-simulado?cita_id=' . $pago->cita_id . '&telefono=' . urlencode($pago->cliente->telefono);
        }

        try {
            $response = Http::withToken($token)
                ->post('https://api.mercadopago.com/checkout/preferences', [
                    'items' => [
                        [
                            'title'       => $pago->es_sena ? 'Seña de Reserva - CitasPro' : 'Pago de Reserva - CitasPro',
                            'quantity'    => 1,
                            'unit_price'  => (float) $pago->monto,
                            'currency_id' => $pago->moneda === 'EUR' ? 'EUR' : 'ARS', // Puede configurarse dinámicamente
                        ]
                    ],
                    'back_urls' => [
                        'success' => config('app.url') . '/dashboard?mp_success=1',
                        'failure' => config('app.url') . '/dashboard?mp_fail=1',
                        'pending' => config('app.url') . '/dashboard?mp_pending=1',
                    ],
                    'auto_return' => 'approved',
                    'external_reference' => (string) $pago->id,
                    'notification_url'   => config('app.url') . '/api/pagos/webhook/mercadopago',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $pago->update([
                    'referencia_externa' => $data['id']
                ]);
                return $data['init_point']; // Link de redirección para el cliente
            }

            Log::error('MercadoPago API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MercadoPago Exception: ' . $e->getMessage());
            return null;
        }
    }
}
