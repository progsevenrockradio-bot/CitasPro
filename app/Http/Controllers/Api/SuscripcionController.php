<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Services\SuscripcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuscripcionController extends Controller
{
    public function __construct(private SuscripcionService $suscripcionService) {}

    /**
     * Devuelve los planes disponibles y sus características.
     * Ruta pública — no requiere autenticación.
     */
    public function planes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'planes'  => $this->suscripcionService->planes(),
        ]);
    }

    /**
     * Inicia el flujo de suscripción: devuelve la URL de Stripe Checkout.
     */
    public function suscribir(Request $request): JsonResponse
    {
        $profesional = $this->getProfesionalDueño($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo el dueño del negocio puede contratar un plan.'], 403);
        }

        $validated = $request->validate([
            'plan' => 'required|in:basic,pro,enterprise',
        ]);

        $negocio = Negocio::findOrFail($profesional->negocio_id);

        try {
            $checkoutUrl = $this->suscripcionService->crearSesionCheckout($negocio, $validated['plan']);

            return response()->json([
                'success'      => true,
                'checkout_url' => $checkoutUrl,
                'message'      => 'Redirige al usuario a la URL de pago para completar la suscripción.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('SuscripcionController: error al crear sesión de checkout: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar la solicitud. Inténtalo de nuevo.'], 500);
        }
    }

    /**
     * Cancela la suscripción del negocio al final del período actual.
     */
    public function cancelar(Request $request): JsonResponse
    {
        $profesional = $this->getProfesionalDueño($request);
        if (!$profesional) {
            return response()->json(['message' => 'Solo el dueño del negocio puede cancelar el plan.'], 403);
        }

        $negocio = Negocio::findOrFail($profesional->negocio_id);

        try {
            $this->suscripcionService->cancelarSuscripcion($negocio);

            return response()->json([
                'success' => true,
                'message' => 'Tu suscripción se cancelará al final del período de facturación actual. Mientras, sigues teniendo acceso completo.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('SuscripcionController: error al cancelar suscripción: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cancelar. Inténtalo de nuevo.'], 500);
        }
    }

    /**
     * Webhook de Stripe para eventos de suscripción.
     * Ruta PÚBLICA — Stripe llama a este endpoint automáticamente.
     */
    public function webhookSuscripcion(Request $request): JsonResponse
    {
        $payload  = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret   = config('services.stripe.webhook_suscripcion_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::warning('SuscripcionController: Webhook inválido: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook inválido'], 400);
        }

        $data = $event->data->object;

        switch ($event->type) {

            // Suscripción actualizada (nuevo pago, cambio de plan)
            case 'customer.subscription.updated':
            case 'customer.subscription.created':
                $negocioId     = $data->metadata->negocio_id ?? null;
                $plan          = $data->metadata->plan ?? 'basic';
                $subscriptionId = $data->id;
                $venceEn       = \DateTime::createFromFormat('U', $data->current_period_end);

                if ($negocioId && $data->status === 'active') {
                    $this->suscripcionService->actualizarPlanDesdeStripe($negocioId, $plan, $subscriptionId, $venceEn);
                }
                break;

            // Suscripción eliminada/cancelada → degradar a free
            case 'customer.subscription.deleted':
                $negocioId = $data->metadata->negocio_id ?? null;
                if ($negocioId) {
                    $negocio = Negocio::find($negocioId);
                    if ($negocio) {
                        $negocio->update([
                            'plan'                   => 'free',
                            'plan_vence_en'          => null,
                            'stripe_subscription_id' => null,
                        ]);
                        Log::info("Webhook: Negocio #{$negocioId} degradado a plan free.");
                    }
                }
                break;

            // Pago de factura fallido — solo loguear por ahora
            case 'invoice.payment_failed':
                $customerId = $data->customer;
                $negocio    = Negocio::where('stripe_customer_id', $customerId)->first();
                if ($negocio) {
                    Log::warning("Webhook: Pago fallido para negocio #{$negocio->id} ({$negocio->nombre}).");
                    // TODO: Enviar notificación al dueño del negocio
                }
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Obtiene el profesional autenticado y verifica que sea dueño o admin.
     */
    private function getProfesionalDueño(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional && in_array($user->rol, ['dueño', 'admin'])) {
            return $user;
        }
        return null;
    }
}
