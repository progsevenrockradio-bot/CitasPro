<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Profesional;
use App\Services\StripeService;
use App\Services\MercadoPagoService;
use App\Services\RedsysService;
use App\Events\PagoConfirmado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Si el usuario es Súper Administrador (modelo User)
        if ($user instanceof \App\Models\User) {
            $pagos = Pago::with(['cliente:id,nombre,apellido,telefono', 'cita:id,servicio_id,fecha,hora_inicio', 'cita.servicio:id,nombre', 'negocio:id,nombre'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'pagos' => $pagos
            ]);
        }

        if (!$user instanceof Profesional) {
            return response()->json(['message' => 'Solo profesionales pueden ver el historial de pagos.'], 403);
        }

        $pagos = Pago::with(['cliente:id,nombre,apellido,telefono', 'cita:id,servicio_id,fecha,hora_inicio', 'cita.servicio:id,nombre'])
            ->where('negocio_id', $user->negocio_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'pagos' => $pagos
        ]);
    }

    /**
     * Procesa la intención de pago para una cita.
     * Permite pagar con "efectivo", "stripe", "mercadopago", "redsys", "bizum".
     */
    public function procesar(
        Request $request, 
        StripeService $stripeService,
        MercadoPagoService $mercadopagoService,
        RedsysService $redsysService
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'metodo'  => 'required|in:efectivo,stripe,mercadopago,redsys,bizum',
        ]);

        $cita = Cita::with(['cliente', 'servicio', 'negocio'])->findOrFail($validated['cita_id']);

        // Verificar que la cita sea de este cliente
        if ($cita->cliente_id !== $user->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Verificar que la cita no esté ya pagada (completada)
        $pagoCompletado = Pago::where('cita_id', $cita->id)->completado()->first();
        if ($pagoCompletado) {
            return response()->json(['message' => 'Esta cita ya ha sido pagada.'], 422);
        }

        // Cálculo de Seña / Depósito
        $servicio = $cita->servicio;
        $montoACobrar = $cita->precio_total;
        $esSena = false;

        if ($servicio->requiere_sena) {
            $esSena = true;
            if ($servicio->tipo_sena === 'porcentaje') {
                $montoACobrar = round(($cita->precio_total * $servicio->valor_sena) / 100, 2);
            } else {
                $montoACobrar = min($servicio->valor_sena, $cita->precio_total);
            }
        }

        // Recuperar o crear el registro de Pago (pendiente)
        $pago = Pago::updateOrCreate(
            ['cita_id' => $cita->id, 'estado' => 'pendiente'],
            [
                'cliente_id'  => $cita->cliente_id,
                'negocio_id'  => $cita->negocio_id,
                'monto'       => $montoACobrar,
                'monto_total' => $cita->precio_total,
                'metodo'      => $validated['metodo'],
                'es_sena'     => $esSena,
                'moneda'      => $servicio->moneda ?: 'EUR',
            ]
        );

        // Si el cliente cambió de opinión y eligió otro método, lo actualizamos
        if ($pago->metodo !== $validated['metodo']) {
            $pago->update([
                'metodo'  => $validated['metodo'],
                'monto'   => $montoACobrar,
                'es_sena' => $esSena,
            ]);
        }

        return $this->generarRespuestaPago($pago, $validated['metodo'], $stripeService, $mercadopagoService, $redsysService);
    }

    /**
     * Procesa la intención de pago para un paciente no autenticado (público).
     */
    public function procesarPagoPaciente(
        Request $request, 
        StripeService $stripeService,
        MercadoPagoService $mercadopagoService,
        RedsysService $redsysService
    ): JsonResponse {
        $validated = $request->validate([
            'cita_id'  => 'required|exists:citas,id',
            'telefono' => 'required|string',
            'metodo'   => 'required|in:efectivo,stripe,mercadopago,redsys,bizum',
        ]);

        $cita = Cita::with(['cliente', 'servicio', 'negocio'])->findOrFail($validated['cita_id']);

        // Normalizar teléfonos para comparar
        $telCita = preg_replace('/[^\d]/', '', $cita->cliente->telefono);
        $telRequest = preg_replace('/[^\d]/', '', $validated['telefono']);

        // Verificar coincidencia de teléfono para autorizar (últimos 7 dígitos)
        if (empty($telCita) || substr($telCita, -7) !== substr($telRequest, -7)) {
            return response()->json(['message' => 'No autorizado. El número de teléfono no coincide con la reserva.'], 403);
        }

        // Verificar que la cita no esté ya pagada
        $pagoCompletado = Pago::where('cita_id', $cita->id)->completado()->first();
        if ($pagoCompletado) {
            return response()->json(['message' => 'Esta cita ya ha sido pagada.'], 422);
        }

        // Cálculo de Seña / Depósito
        $servicio = $cita->servicio;
        $montoACobrar = $cita->precio_total;
        $esSena = false;

        if ($servicio->requiere_sena) {
            $esSena = true;
            if ($servicio->tipo_sena === 'porcentaje') {
                $montoACobrar = round(($cita->precio_total * $servicio->valor_sena) / 100, 2);
            } else {
                $montoACobrar = min($servicio->valor_sena, $cita->precio_total);
            }
        }

        // Recuperar o crear el registro de Pago (pendiente)
        $pago = Pago::updateOrCreate(
            ['cita_id' => $cita->id, 'estado' => 'pendiente'],
            [
                'cliente_id'  => $cita->cliente_id,
                'negocio_id'  => $cita->negocio_id,
                'monto'       => $montoACobrar,
                'monto_total' => $cita->precio_total,
                'metodo'      => $validated['metodo'],
                'es_sena'     => $esSena,
                'moneda'      => $servicio->moneda ?: 'EUR',
            ]
        );

        if ($pago->metodo !== $validated['metodo']) {
            $pago->update([
                'metodo'  => $validated['metodo'],
                'monto'   => $montoACobrar,
                'es_sena' => $esSena,
            ]);
        }

        return $this->generarRespuestaPago($pago, $validated['metodo'], $stripeService, $mercadopagoService, $redsysService);
    }

    /**
     * Helper para procesar cada método de pago y devolver la respuesta adecuada.
     */
    private function generarRespuestaPago(
        Pago $pago, 
        string $metodo, 
        StripeService $stripeService,
        MercadoPagoService $mercadopagoService,
        RedsysService $redsysService
    ): JsonResponse {
        if ($metodo === 'efectivo') {
            return response()->json([
                'success' => true,
                'message' => 'Pago en efectivo registrado. Se cobrará en el local.',
                'metodo'  => 'efectivo',
                'pago_id' => $pago->id,
                'monto'   => $pago->monto
            ]);
        }

        if ($metodo === 'stripe') {
            try {
                $clientSecret = $stripeService->crearPaymentIntent($pago);
                return response()->json([
                    'success'       => true,
                    'message'       => 'Intención de pago creada.',
                    'metodo'        => 'stripe',
                    'client_secret' => $clientSecret,
                    'pago_id'       => $pago->id,
                    'monto'         => $pago->monto
                ]);
            } catch (\Exception $e) {
                Log::error('Error creando Stripe PaymentIntent: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar el pago con tarjeta en este momento.'
                ], 500);
            }
        }

        if ($metodo === 'mercadopago') {
            $urlRedirect = $mercadopagoService->crearPreferencia($pago);
            if ($urlRedirect) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preferencia de MercadoPago generada.',
                    'metodo'  => 'mercadopago',
                    'url'     => $urlRedirect,
                    'pago_id' => $pago->id,
                    'monto'   => $pago->monto
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'No se pudo inicializar MercadoPago.'
            ], 500);
        }

        if ($metodo === 'redsys' || $metodo === 'bizum') {
            $esBizum = ($metodo === 'bizum');
            $redsysData = $redsysService->obtenerParametrosFormulario($pago, $esBizum);
            
            return response()->json([
                'success' => true,
                'message' => 'Parámetros de Redsys/Bizum preparados.',
                'metodo'  => $metodo,
                'redsys'  => $redsysData,
                'pago_id' => $pago->id,
                'monto'   => $pago->monto
            ]);
        }

        return response()->json(['message' => 'Método no soportado.'], 422);
    }

    /**
     * Simula la confirmación exitosa de un pago (cualquier pasarela) en modo demo/local.
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
                'estado'    => 'completado',
                'pagado_en' => now(),
                'metadata'  => array_merge($pago->metadata ?? [], ['simulado' => true])
            ]);

            $cita->update(['estado' => 'confirmada']);

            // Notificaciones de cita actualizada
            event(new \App\Events\CitaActualizada($cita));
            // Notificaciones de pago confirmado (Email + WhatsApp)
            event(new PagoConfirmado($pago->load(['cliente', 'negocio', 'cita.servicio'])));
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
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object; 
            
            $pago = Pago::where('referencia_externa', $paymentIntent->id)->first();
            
            if ($pago && !$pago->estaCompletado()) {
                $pago->update([
                    'estado'    => 'completado',
                    'pagado_en' => now(),
                    'metadata'  => array_merge($pago->metadata ?? [], ['stripe_payment_intent' => $paymentIntent->toArray()])
                ]);

                $pago->cita()->update(['estado' => 'confirmada']);
                event(new \App\Events\CitaActualizada($pago->cita));
                event(new PagoConfirmado($pago->load(['cliente', 'negocio', 'cita.servicio'])));
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Webhook público para recibir notificaciones de MercadoPago (IPN/Webhooks).
     *
     * MercadoPago envía el webhook en DOS formatos:
     *   - IPN clásico:        POST body con `external_reference` y `status`
     *   - Webhook moderno:    POST body con `data.id` (payment_id) SIN external_reference
     *
     * En el formato moderno, necesitamos llamar a la API de MP para obtener el
     * external_reference y el status real del pago.
     */
    public function webhookMercadoPago(Request $request): JsonResponse
    {
        Log::info('PagoController: Webhook MercadoPago recibido.', $request->all());

        // ── Extraer el Payment ID del payload (ambos formatos) ─────────────────
        $paymentId = $request->input('data.id')    // Formato moderno (JSON body)
                  ?? $request->input('id')           // IPN alternativo
                  ?? $request->query('id');           // IPN clásico por query string

        if (!$paymentId) {
            Log::warning('PagoController: Webhook MP sin payment ID, ignorado.');
            return response()->json(['status' => 'ignored']);
        }

        // ── Determinar el token a usar ─────────────────────────────────────────
        // Intentamos recuperar el pago por external_reference si viene en el payload
        $pagoIdFromPayload = $request->input('external_reference');
        $pagoProvisional   = $pagoIdFromPayload ? Pago::with('negocio')->find($pagoIdFromPayload) : null;
        $token = $pagoProvisional?->negocio?->mp_access_token
               ?: config('services.mercadopago.access_token');

        // ── Modo simulación en local ────────────────────────────────────────────
        if (empty($token) || $token === 'Coloca_Aqui_Tu_MercadoPago_Access_Token') {
            // Sin token real, confiamos en el payload directamente
            $status = $request->input('status', 'approved');
            $mpData = ['simulated' => true, 'payment_id' => $paymentId];
            $pagoId = $pagoIdFromPayload;

            if (!$pagoId) {
                Log::warning('PagoController: Webhook MP simulado sin external_reference, ignorado.');
                return response()->json(['status' => 'ignored']);
            }
        } else {
            // ── Llamada real a la API de MercadoPago ───────────────────────────
            // Esto resuelve el problema: cuando el webhook moderno NO incluye
            // external_reference, la llamamos explícitamente para obtenerlo.
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (!$response->successful()) {
                Log::error("PagoController: Error al verificar pago MP #{$paymentId} — HTTP {$response->status()}");
                return response()->json(['status' => 'error'], 500);
            }

            $mpData = $response->json();
            $status = $mpData['status'] ?? 'pending';

            // external_reference es nuestro ID de Pago — viene siempre en la API aunque
            // no venga en el webhook payload
            $pagoId = $mpData['external_reference'] ?? $pagoIdFromPayload;

            if (!$pagoId) {
                Log::warning("PagoController: Pago MP #{$paymentId} sin external_reference, ignorado.");
                return response()->json(['status' => 'ignored']);
            }
        }

        // ── Buscar el Pago local ────────────────────────────────────────────────
        $pago = Pago::with('negocio')->find($pagoId);
        if (!$pago) {
            Log::error("PagoController: Pago #{$pagoId} no encontrado para webhook MP #{$paymentId}");
            return response()->json(['status' => 'not_found'], 404);
        }

        // ── Procesar según el estado ────────────────────────────────────────────
        if ($status === 'approved' || $status === 'completed') {
            if (!$pago->estaCompletado()) {
                $pago->update([
                    'estado'    => 'completado',
                    'pagado_en' => now(),
                    'metadata'  => array_merge($pago->metadata ?? [], ['mp_webhook' => $mpData])
                ]);

                $pago->cita()->update(['estado' => 'confirmada']);
                event(new \App\Events\CitaActualizada($pago->cita));
                event(new PagoConfirmado($pago->load(['cliente', 'negocio', 'cita.servicio'])));
                Log::info("PagoController: Pago #{$pagoId} confirmado vía MercadoPago (MP payment #{$paymentId}).");
            }
        } elseif ($status === 'rejected' || $status === 'cancelled') {
            Log::info("PagoController: Pago MP #{$paymentId} rechazado/cancelado — estado MP: {$status}");
        }

        return response()->json(['status' => 'success']);
    }


    /**
     * Webhook público para recibir notificaciones online de Redsys.
     */
    public function webhookRedsys(Request $request): JsonResponse
    {
        $merchantParams = $request->input('Ds_MerchantParameters');
        
        if ($merchantParams) {
            $decoded = json_decode(base64_decode($merchantParams), true);
            Log::info('PagoController: Webhook Redsys recibido.', $decoded);
            
            $order = $decoded['Ds_Order'] ?? null;
            $responseCode = isset($decoded['Ds_Response']) ? (int) $decoded['Ds_Response'] : 999;
            
            // Redsys considera éxito valores de 0000 a 0099
            if ($order && $responseCode >= 0 && $responseCode <= 99) {
                // Quitar ceros a la izquierda
                $pagoId = (int) ltrim($order, '0');
                $pago = Pago::find($pagoId);
                
                if ($pago && !$pago->estaCompletado()) {
                    $pago->update([
                        'estado'    => 'completado',
                        'pagado_en' => now(),
                        'metadata'  => array_merge($pago->metadata ?? [], ['redsys_webhook' => $decoded])
                    ]);

                    $pago->cita()->update(['estado' => 'confirmada']);
                    event(new \App\Events\CitaActualizada($pago->cita));
                    event(new PagoConfirmado($pago->load(['cliente', 'negocio', 'cita.servicio'])));
                    Log::info("PagoController: Pago #{$pagoId} confirmado vía Redsys.");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Descarga el recibo / factura en formato PDF de un pago específico.
     */
    public function descargarFacturaPdf(Request $request, Pago $pago)
    {
        $user = $request->user();

        // Verificar permisos
        if ($user->rol !== 'superadmin') {
            if ($user->rol === 'dueño' || $user->rol === 'admin') {
                if ($pago->negocio_id !== $user->negocio_id) {
                    return response()->json(['message' => 'Acceso denegado.'], 403);
                }
            } else {
                if ($pago->cita && $pago->cita->profesional_id !== $user->id) {
                    return response()->json(['message' => 'Acceso denegado.'], 403);
                }
            }
        }

        // Cargar las relaciones necesarias
        $pago->load(['cliente', 'negocio.datosFiscales', 'cita.servicio', 'cita.profesional']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.factura_pdf', [
            'pago' => $pago,
            'negocio' => $pago->negocio,
            'cliente' => $pago->cliente,
            'cita' => $pago->cita,
        ]);

        return $pdf->download("Factura_{$pago->id}.pdf");
    }
}
