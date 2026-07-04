<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

/**
 * SmsService — Servicio SMS de respaldo (fallback).
 *
 * Estrategia de envío (por orden de prioridad):
 *   1. Twilio SDK (si está configurado)
 *   2. Email-to-SMS (gratuito, vía gateway del operador)
 *   3. Log (simulación en desarrollo)
 *
 * Uso típico: se invoca si WhatsApp falla.
 */
class SmsService
{
    private bool   $simularTwilio;
    private bool   $emailSmsActivo;
    private ?string $twilioSid;
    private ?string $twilioToken;
    private ?string $twilioFrom;
    private ?string $emailSmsGateway;

    public function __construct()
    {
        $this->simularTwilio   = filter_var(config('services.twilio.simular', true), FILTER_VALIDATE_BOOLEAN);
        $this->twilioSid       = config('services.twilio.sid', '');
        $this->twilioToken     = config('services.twilio.token', '');
        $this->twilioFrom      = config('services.twilio.from', '');

        $this->emailSmsActivo   = filter_var(config('services.email_sms.activo', false), FILTER_VALIDATE_BOOLEAN);
        $this->emailSmsGateway  = config('services.email_sms.gateway', '@sms.movistar.es');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mensajes predefinidos para citas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía SMS de recordatorio de cita.
     */
    public function enviarRecordatorio(Cita $cita): bool
    {
        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;
        $negocio  = $cita->negocio;

        $fecha = \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y');
        $hora  = substr($cita->hora_inicio, 0, 5);

        $texto = "CitasPro: Recordatorio! {$cliente->nombre}, tienes {$servicio->nombre} "
               . "el {$fecha} a las {$hora} en {$negocio->nombre}. "
               . "Ref: {$cita->codigo_referencia}";

        return $this->enviar($cliente->telefono, $texto);
    }

    /**
     * Envía SMS de confirmación de nueva cita.
     */
    public function enviarConfirmacion(Cita $cita): bool
    {
        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;
        $negocio  = $cita->negocio;

        $fecha = \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y');
        $hora  = substr($cita->hora_inicio, 0, 5);

        $texto = "CitasPro: Cita confirmada! {$servicio->nombre} el {$fecha} a las {$hora} "
               . "en {$negocio->nombre}. Ref: {$cita->codigo_referencia}";

        return $this->enviar($cliente->telefono, $texto);
    }

    /**
     * Envía SMS de cancelación de cita.
     */
    public function enviarCancelacion(Cita $cita): bool
    {
        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;

        $fecha = \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y');
        $hora  = substr($cita->hora_inicio, 0, 5);

        $texto = "CitasPro: Cita cancelada. {$servicio->nombre} del {$fecha} a las {$hora} "
               . "ha sido cancelada. Ref: {$cita->codigo_referencia}. Disculpe las molestias.";

        return $this->enviar($cliente->telefono, $texto);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método central de envío con fallback en cascada
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía un SMS al número dado usando la estrategia disponible.
     *
     * Cascada: Twilio → Email-to-SMS → Log (simulación)
     *
     * @param  string $destinatario  Número E.164
     * @param  string $texto         Texto del SMS (máx. 160 caracteres recomendado)
     * @return bool   true si el SMS fue enviado (o simulado)
     */
    public function enviar(string $destinatario, string $texto): bool
    {
        // Truncar a 160 caracteres para garantizar un solo SMS
        if (strlen($texto) > 160) {
            $texto = substr($texto, 0, 157) . '...';
        }

        // ── Intento 1: Twilio ────────────────────────────────────────────────
        if ($this->simularTwilio) {
            Log::channel('stack')->info("📟 [SMS/Twilio SIMULADO] → {$destinatario}", [
                'texto' => $texto,
            ]);
            return true;
        }

        if ($this->twilioConfigurado()) {
            $enviado = $this->enviarViaTwilio($destinatario, $texto);

            if ($enviado) {
                return true;
            }

            Log::warning("SmsService: Twilio falló para {$destinatario}. Intentando Email-to-SMS...");
        }

        // ── Intento 2: Email-to-SMS ──────────────────────────────────────────
        if ($this->emailSmsActivo) {
            $enviado = $this->enviarViaEmailSms($destinatario, $texto);

            if ($enviado) {
                return true;
            }

            Log::warning("SmsService: Email-to-SMS falló para {$destinatario}.");
        }

        // ── Fallback final: solo log ─────────────────────────────────────────
        Log::error("SmsService: No se pudo enviar SMS a {$destinatario}. Sin canales disponibles.", [
            'texto' => $texto,
        ]);

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Implementaciones concretas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía SMS via Twilio REST API.
     */
    private function enviarViaTwilio(string $destinatario, string $texto): bool
    {
        try {
            $client = new TwilioClient($this->twilioSid, $this->twilioToken);

            $message = $client->messages->create($destinatario, [
                'from' => $this->twilioFrom,
                'body' => $texto,
            ]);

            Log::info("SMS/Twilio enviado OK → {$destinatario}", [
                'sid'    => $message->sid,
                'status' => $message->status,
            ]);

            return true;

        } catch (TwilioException $e) {
            Log::error("Twilio exception → {$destinatario}", [
                'code'  => $e->getCode(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envía SMS via Email-to-SMS (gateway del operador).
     *
     * Muchos operadores permiten enviar SMS desde email:
     *   +34612345678@sms.movistar.es  → Movistar España
     *   +34612345678@correo.vodafone.es → Vodafone España
     *   +34612345678@orange.es         → Orange España
     *
     * NOTA: Funcionalidad limitada y no garantizada por los operadores.
     */
    private function enviarViaEmailSms(string $destinatario, string $texto): bool
    {
        try {
            // Convierte el número E.164 a dirección de email-SMS
            // +34612345678 → 34612345678@sms.movistar.es
            $numero = ltrim($destinatario, '+');
            $emailSms = $numero . $this->emailSmsGateway;

            Mail::raw($texto, function ($message) use ($emailSms) {
                $message->to($emailSms)
                    ->subject(''); // Asunto vacío (algunos gateways lo ignoran)
            });

            Log::info("SMS/Email-to-SMS enviado → {$destinatario} via {$emailSms}");

            return true;

        } catch (\Exception $e) {
            Log::error("Email-to-SMS exception → {$destinatario}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function twilioConfigurado(): bool
    {
        return !empty($this->twilioSid)
            && !empty($this->twilioToken)
            && !empty($this->twilioFrom);
    }
}
