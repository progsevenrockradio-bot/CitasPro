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
        // Cargar el negocio asociado al pago
        $negocio = $pago->negocio;
        
        // Usar clave específica del negocio, o global como fallback
        $secret = $negocio && $negocio->stripe_secret_key ? $negocio->stripe_secret_key : config('services.stripe.secret');

        // Si no hay clave configurada en ningún lado, simulamos para demostración
        if (empty($secret)) {
            $mockIntentId = 'pi_mock_' . uniqid();
            $pago->update([
                'referencia_externa' => $mockIntentId
            ]);
            return 'pi_mock_secret_' . uniqid();
        }

        // Configurar la API key de Stripe dinámicamente
        Stripe::setApiKey($secret);

        // Stripe maneja el monto en centavos (o la unidad menor de la moneda)
        // Ejemplo: 10.50 EUR -> 1050
        $montoEnCentavos = (int) round($pago->monto * 100);

        $intentClass = '\Stripe\PaymentIntent';
        $paymentIntent = $intentClass::create([
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

    /**
     * Crea una Checkout Session de Stripe y devuelve la URL para redirigir al cliente.
     *
     * @return string
     */
    public function crearCheckoutSession(Pago $pago, string $successUrl, string $cancelUrl): string
    {
        $negocio = $pago->negocio;
        $secret = $negocio && $negocio->stripe_secret_key ? $negocio->stripe_secret_key : config('services.stripe.secret');

        if (empty($secret)) {
            // Mock para demostración si no hay claves
            $pago->update(['referencia_externa' => 'cs_mock_' . uniqid()]);
            return $successUrl;
        }

        Stripe::setApiKey($secret);

        $montoEnCentavos = (int) round($pago->monto * 100);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($pago->moneda ?: 'eur'),
                    'product_data' => [
                        'name' => 'Reserva de Cita - ' . ($pago->cita->servicio->nombre ?? 'Servicio'),
                        'description' => 'Pago por reserva en ' . ($negocio->nombre ?? 'nuestro centro'),
                    ],
                    'unit_amount' => $montoEnCentavos,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $pago->id,
            'metadata' => [
                'pago_id' => $pago->id,
                'cita_id' => $pago->cita_id,
            ],
        ]);

        $pago->update([
            'referencia_externa' => $session->id
        ]);

        return $session->url;
    }
}
