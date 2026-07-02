<?php

namespace App\Services;

use App\Models\Negocio;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Subscription;
use Illuminate\Support\Facades\Log;

class SuscripcionService
{
    // Mapeo de planes a Price IDs de Stripe
    private array $precios = [];

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $this->precios = [
            'basic'      => config('services.stripe.precio_basic'),
            'pro'        => config('services.stripe.precio_pro'),
            'enterprise' => config('services.stripe.precio_enterprise'),
        ];
    }

    /**
     * Devuelve los planes disponibles con sus precios y características.
     */
    public function planes(): array
    {
        return [
            'free' => [
                'nombre'     => 'Free',
                'precio_mes' => 0,
                'moneda'     => 'EUR',
                'limite_profesionales' => 1,
                'limite_citas_mes'     => 12, // Límite reducido a 12 citas/mes
                'portafolio'           => true, // Habilitado como gancho visual (freemium agresivo)
                'soporte'              => 'comunidad',
            ],
            'basic' => [
                'nombre'     => 'Basic (Single Pro)',
                'precio_mes' => 9,
                'moneda'     => 'EUR',
                'stripe_price_id' => $this->precios['basic'],
                'limite_profesionales' => 1,
                'limite_citas_mes'     => 80, // Límite de 80 citas al mes
                'portafolio'           => true,
                'soporte'              => 'email',
            ],
            'pro' => [
                'nombre'     => 'Pro (Pyme Multi)',
                'precio_mes' => 17, // Precio reducido a 17€/mes
                'moneda'     => 'EUR',
                'stripe_price_id' => $this->precios['pro'],
                'limite_profesionales' => 5,
                'limite_citas_mes'     => null, // Citas ilimitadas
                'portafolio'           => true,
                'soporte'              => 'prioritario',
            ],
            'enterprise' => [
                'nombre'     => 'Enterprise',
                'precio_mes' => 30, // Precio reducido a 30€/mes
                'moneda'     => 'EUR',
                'stripe_price_id' => $this->precios['enterprise'],
                'limite_profesionales' => null, // Ilimitados
                'limite_citas_mes'     => null, // Ilimitadas
                'portafolio'           => true,
                'soporte'              => 'dedicado',
            ],
        ];
    }

    /**
     * Crea o recupera el Customer de Stripe para el negocio.
     */
    public function crearOObtenerCliente(Negocio $negocio): Customer
    {
        if ($negocio->stripe_customer_id) {
            return Customer::retrieve($negocio->stripe_customer_id);
        }

        $customer = Customer::create([
            'name'     => $negocio->nombre,
            'email'    => $negocio->email,
            'metadata' => ['negocio_id' => $negocio->id],
        ]);

        $negocio->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Crea una Stripe Checkout Session para suscripción mensual.
     * Devuelve la URL a la que redirigir al profesional.
     */
    public function crearSesionCheckout(Negocio $negocio, string $plan): string
    {
        $priceId = (string) ($this->precios[$plan] ?? '');

        if (empty($priceId)) {
            throw new \InvalidArgumentException("Plan '{$plan}' no válido o no configurado en Stripe.");
        }

        $customer = $this->crearOObtenerCliente($negocio);
        $customerId = (string) $customer->id;
        $negocioId = (string) $negocio->id;

        $params = [
            'customer'            => $customerId,
            'mode'                => 'subscription',
            'line_items'          => [['price' => $priceId, 'quantity' => 1]],
            'success_url'         => config('app.url') . '/suscripcion/exito?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => config('app.url') . '/suscripcion/cancelado',
            'metadata'            => ['negocio_id' => $negocioId, 'plan' => $plan],
            'subscription_data'   => [
                'metadata' => ['negocio_id' => $negocioId, 'plan' => $plan],
            ],
        ];

        $sessionClass = '\Stripe\Checkout\Session';
        $session = $sessionClass::create($params);

        return $session->url;
    }

    /**
     * Cancela la suscripción al final del período actual (no de inmediato).
     */
    public function cancelarSuscripcion(Negocio $negocio): void
    {
        if (!$negocio->stripe_subscription_id) {
            throw new \RuntimeException('Este negocio no tiene una suscripción activa.');
        }

        $subscription = Subscription::retrieve($negocio->stripe_subscription_id);
        $subscription->cancel(['cancel_at_period_end' => true]);

        Log::info("SuscripcionService: Suscripción {$negocio->stripe_subscription_id} marcada para cancelar al final del período. Negocio #{$negocio->id}");
    }

    /**
     * Actualiza el plan del negocio en base a la suscripción de Stripe.
     */
    public function actualizarPlanDesdeStripe(string $negocioId, string $plan, string $subscriptionId, \DateTime $venceEn): void
    {
        $negocio = Negocio::find($negocioId);

        if (!$negocio) {
            Log::error("SuscripcionService: negocio #{$negocioId} no encontrado al actualizar plan.");
            return;
        }

        $negocio->update([
            'plan'                   => $plan,
            'plan_vence_en'          => $venceEn,
            'stripe_subscription_id' => $subscriptionId,
            'activo'                 => true,
        ]);

        Log::info("SuscripcionService: Plan de negocio #{$negocioId} actualizado a '{$plan}'.");
    }
}
