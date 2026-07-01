<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    /**
     * Procesa la intención de pago para una cita.
     * Permite pagar con "efectivo" o "stripe".
     */
    public function procesar(Request $request, StripeService $stripeService): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'metodo'  => 'required|in:efectivo,stripe',
        ]);

        $cita = Cita::findOrFail($validated['cita_id']);

        // Verificar que la cita sea de este cliente
        if ($cita->cliente_id !== $user->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Verificar que la cita no esté ya pagada (completada)
        // Buscamos si ya existe un pago completado para esta cita
        $pagoCompletado = Pago::where('cita_id', $cita->id)->completado()->first();
        if ($pagoCompletado) {
            return response()->json(['message' => 'Esta cita ya ha sido pagada.'], 422);
        }

        // Recuperar o crear el registro de Pago (pendiente)
        $pago = Pago::firstOrCreate(
            ['cita_id' => $cita->id, 'estado' => 'pendiente'],
            [
                'cliente_id' => $cita->cliente_id,
                'negocio_id' => $cita->negocio_id,
                'monto' => $cita->precio_total, // Suponiendo que Cita tiene precio_total
                'monto_total' => $cita->precio_total,
                'metodo' => $validated['metodo'],
            ]
        );

        // Si el cliente cambió de opinión y eligió otro método, lo actualizamos
        if ($pago->metodo !== $validated['metodo']) {
            $pago->update(['metodo' => $validated['metodo']]);
        }

        // Lógica según el método
        if ($validated['metodo'] === 'efectivo') {
            return response()->json([
                'success' => true,
                'message' => 'Pago en efectivo registrado. Se cobrará en el local.',
                'metodo' => 'efectivo',
                'pago_id' => $pago->id
            ]);
        }

        // Lógica para Stripe
        if ($validated['metodo'] === 'stripe') {
            try {
                $clientSecret = $stripeService->crearPaymentIntent($pago);
                return response()->json([
                    'success' => true,
                    'message' => 'Intención de pago creada.',
                    'metodo' => 'stripe',
                    'client_secret' => $clientSecret,
                    'pago_id' => $pago->id
                ]);
            } catch (\Exception $e) {
                Log::error('Error creando Stripe PaymentIntent: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar el pago con tarjeta en este momento.'
                ], 500);
            }
        }

        return response()->json(['message' => 'Método no soportado.'], 422);
    }

    /**
     * Procesa la intención de pago para un paciente no autenticado (público).
     */
    public function procesarPagoPaciente(Request $request, StripeService $stripeService): JsonResponse
    {
        $validated = $request->validate([
            'cita_id'  => 'required|exists:citas,id',
            'telefono' => 'required|string',
            'metodo'   => 'required|in:efectivo,stripe',
        ]);

        $cita = Cita::with('cliente')->findOrFail($validated['cita_id']);

        // Normalizar teléfonos para comparar
        $telCita = preg_replace('/[^\d]/', '', $cita->cliente->telefono);
        $telRequest = preg_replace('/[^\d]/', '', $validated['telefono']);

        // Verificar coincidencia de teléfono para autorizar (últimos 7 dígitos para evitar temas de prefijos)
        if (empty($telCita) || substr($telCita, -7) !== substr($telRequest, -7)) {
            return response()->json(['message' => 'No autorizado. El número de teléfono no coincide con la reserva.'], 403);
        }

        // Verificar que la cita no esté ya pagada
        $pagoCompletado = Pago::where('cita_id', $cita->id)->completado()->first();
        if ($pagoCompletado) {
            return response()->json(['message' => 'Esta cita ya ha sido pagada.'], 422);
        }

        // Recuperar o crear el registro de Pago (pendiente)
        $pago = Pago::firstOrCreate(
            ['cita_id' => $cita->id, 'estado' => 'pendiente'],
            [
                'cliente_id' => $cita->cliente_id,
                'negocio_id' => $cita->negocio_id,
                'monto' => $cita->precio_total,
                'monto_total' => $cita->precio_total,
                'metodo' => $validated['metodo'],
            ]
        );

        if ($pago->metodo !== $validated['metodo']) {
            $pago->update(['metodo' => $validated['metodo']]);
        }

        if ($validated['metodo'] === 'efectivo') {
            return response()->json([
                'success' => true,
                'message' => 'Pago en efectivo registrado. Se cobrará en el local.',
                'metodo' => 'efectivo',
                'pago_id' => $pago->id
            ]);
        }

        if ($validated['metodo'] === 'stripe') {
            try {
                $clientSecret = $stripeService->crearPaymentIntent($pago);
                return response()->json([
                    'success' => true,
                    'message' => 'Intención de pago creada.',
                    'metodo' => 'stripe',
                    'client_secret' => $clientSecret,
                    'pago_id' => $pago->id
                ]);
            } catch (\Exception $e) {
                Log::error('Error creando Stripe PaymentIntent para paciente: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar el pago con tarjeta en este momento.'
                ], 500);
            }
        }

        return response()->json(['message' => 'Método no soportado.'], 422);
    }

    /**
     * Simula la confirmación exitosa de un pago (Stripe) en modo demo/local.
     */
    public function confirmarPagoPacienteSimulado(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cita_id'  => 'required|exists:citas,id',
            'telefono' => 'required|string',
        ]);

        $cita = Cita::with('cliente')->findOrFail($validated['cita_id']);

        // Verificar coincidencia de teléfono
        $telCita = preg_replace('/[^\d]/', '', $cita->cliente->telefono);
        $telRequest = preg_replace('/[^\d]/', '', $validated['telefono']);

        if (empty($telCita) || substr($telCita, -7) !== substr($telRequest, -7)) {
            return response()->json(['message' => 'No autorizado. El número de teléfono no coincide con la reserva.'], 403);
        }

        $pago = Pago::where('cita_id', $cita->id)->first();
        if ($pago) {
            $pago->update([
                'estado' => 'completado',
                'pagado_en' => now(),
                'metadata' => ['simulado' => true]
            ]);

            $cita->update(['estado' => 'confirmada']);
            
            // Disparar evento para flujo de notificaciones
            event(new \App\Events\CitaActualizada($cita));
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago simulado confirmado con éxito.'
        ]);
    }

    /**
     * Webhook público para recibir notificaciones de Stripe.
     */
    public function webhookStripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            // Verifica la firma para asegurarse de que viene de Stripe
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Manejar el evento de pago exitoso
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object; 
            
            // Buscar el pago por la referencia_externa (el ID del payment_intent)
            $pago = Pago::where('referencia_externa', $paymentIntent->id)->first();
            
            if ($pago && !$pago->estaCompletado()) {
                $pago->update([
                    'estado' => 'completado',
                    'pagado_en' => now(),
                    'metadata' => ['stripe_payment_intent' => $paymentIntent->toArray()]
                ]);

                // Actualizar estado de la cita
                $pago->cita()->update(['estado' => 'confirmada']); // Asumiendo 'confirmada' significa pagada
            }
        }

        return response()->json(['status' => 'success']);
    }
}
