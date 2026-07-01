<?php

namespace App\Services;

use App\Models\Pago;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if ($secret) {
            Stripe::setApiKey($secret);
        }
    }

    /**
     * Crea un PaymentIntent en Stripe para un pago específico.
     * Devuelve el client_secret para el frontend.
     */
    public function crearPaymentIntent(Pago $pago): string
    {
        $secret = config('services.stripe.secret');

        // Si no hay clave configurada en .env, simulamos para demostración
        if (empty($secret)) {
            $mockIntentId = 'pi_mock_' . uniqid();
            $pago->update([
                'referencia_externa' => $mockIntentId
            ]);
            return 'pi_mock_secret_' . uniqid();
        }

        // Stripe maneja el monto en centavos (o la unidad menor de la moneda)
        // Ejemplo: 10.50 EUR -> 1050
        $montoEnCentavos = (int) round($pago->monto_total * 100);

        $paymentIntent = PaymentIntent::create([
            'amount' => $montoEnCentavos,
            'currency' => strtolower($pago->moneda),
            'metadata' => [
                'pago_id' => $pago->id,
                'cita_id' => $pago->cita_id,
                'cliente_id' => $pago->cliente_id,
                'negocio_id' => $pago->negocio_id,
            ],
        ]);

        // Actualizamos la referencia externa en nuestro sistema
        $pago->update([
            'referencia_externa' => $paymentIntent->id
        ]);

        return $paymentIntent->client_secret;
    }
}
