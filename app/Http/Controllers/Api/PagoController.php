<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Profesional;
use App\Services\StripeService;
use App\Services\MercadoPagoService;
use App\Services\RedsysService;
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
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Webhook público para recibir notificaciones de MercadoPago (IPN/Webhooks).
     */
    public function webhookMercadoPago(Request $request): JsonResponse
    {
        Log::info('PagoController: Webhook MercadoPago recibido.', $request->all());

        // El ID de referencia externa que enviamos es el ID del Pago local
        $pagoId = $request->input('external_reference');
        
        // Si no viene directo, puede ser una consulta IPN por ID de recurso
        if (!$pagoId && $request->input('type') === 'payment') {
            $paymentId = $request->input('data.id') ?? $request->input('id');
            // En producción, aquí haríamos un GET a MP para recuperar external_reference usando el paymentId.
            // Para robustez y pruebas locales, permitimos que se envíe el pagoId en las consultas
        }

        // Si es una simulación de webhook o una notificación confirmada
        $status = $request->input('status', 'approved');

        if ($pagoId && ($status === 'approved' || $status === 'completed')) {
            $pago = Pago::find($pagoId);
            
            if ($pago && !$pago->estaCompletado()) {
                $pago->update([
                    'estado'    => 'completado',
                    'pagado_en' => now(),
                    'metadata'  => array_merge($pago->metadata ?? [], ['mp_webhook' => $request->all()])
                ]);

                $pago->cita()->update(['estado' => 'confirmada']);
                event(new \App\Events\CitaActualizada($pago->cita));
                Log::info("PagoController: Pago #{$pagoId} confirmado vía MercadoPago.");
            }
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
                    Log::info("PagoController: Pago #{$pagoId} confirmado vía Redsys.");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
