<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services — CitasPro
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── WhatsApp Cloud API (Meta) ─────────────────────────────────────────────
    'whatsapp' => [
        'token'        => env('WHATSAPP_TOKEN'),           // Bearer token de Meta
        'phone_id'     => env('WHATSAPP_PHONE_ID'),        // ID del número de envío
        'from_number'  => env('WHATSAPP_FROM_NUMBER'),     // Número E.164 del negocio
        'api_url'      => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v19.0'),
        // ID de plantilla aprobada en Meta Business Manager
        'template_recordatorio' => env('WHATSAPP_TEMPLATE_RECORDATORIO', 'recordatorio_cita'),
        'template_confirmacion' => env('WHATSAPP_TEMPLATE_CONFIRMACION', 'confirmacion_cita'),
        'template_cancelacion'  => env('WHATSAPP_TEMPLATE_CANCELACION', 'cancelacion_cita'),
        'simular' => env('WHATSAPP_SIMULAR', true),        // true = solo log, sin llamada real
    ],

    // ── Telegram Bot API ──────────────────────────────────────────────────────
    'telegram' => [
        'bot_token'    => env('TELEGRAM_BOT_TOKEN'),          // Token del BotFather
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'CitasProAlertsBot'), // Username sin @
        'api_url'      => 'https://api.telegram.org/bot',
        'simular'      => env('TELEGRAM_SIMULAR', true),      // true = solo log
    ],

    // ── Twilio (SMS de respaldo) ──────────────────────────────────────────────
    'twilio' => [
        'sid'       => env('TWILIO_SID'),
        'token'     => env('TWILIO_TOKEN'),
        'from'      => env('TWILIO_FROM'),                 // Número Twilio E.164
        'simular'   => env('TWILIO_SIMULAR', true),        // true = solo log
    ],

    // ── Email-to-SMS (respaldo gratuito) ─────────────────────────────────────
    // Algunos operadores permiten enviar SMS via email: +34612345678@sms.movistar.es
    'email_sms' => [
        'activo'  => env('EMAIL_SMS_ACTIVO', false),
        'gateway' => env('EMAIL_SMS_GATEWAY', '@sms.movistar.es'), // Gateway del operador
    ],

    // ── Notificaciones generales ──────────────────────────────────────────────
    'notificaciones' => [
        // Horas antes de la cita para enviar el recordatorio
        'recordatorio_horas_antes' => env('RECORDATORIO_HORAS_ANTES', 24),
        // También enviar si la cita es en menos de X minutos
        'recordatorio_minutos_urgente' => env('RECORDATORIO_MINUTOS_URGENTE', 60),
    ],

    // ── Stripe ────────────────────────────────────────────────────────────────
    'stripe' => [
        'key'                      => env('STRIPE_KEY'),
        'secret'                   => env('STRIPE_SECRET'),
        'webhook_secret'           => env('STRIPE_WEBHOOK_SECRET'),
        // Webhooks de suscripciones (distinto al de pagos de citas)
        'webhook_suscripcion_secret' => env('STRIPE_WEBHOOK_SUSCRIPCION_SECRET'),
        // Price IDs de los planes de suscripción mensual
        'precio_basic'      => env('STRIPE_PRICE_BASIC'),
        'precio_pro'        => env('STRIPE_PRICE_PRO'),
        'precio_enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
    ],

    // ── Google API ────────────────────────────────────────────────────────────
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
    ],

    // ── WhatsApp QR API (Evolution API) ───────────────────────────────────────
    'whatsapp_qr' => [
        'api_url' => env('EVOLUTION_API_URL', 'https://api.tuservidor.com'),
        'api_key' => env('EVOLUTION_API_KEY', 'su_api_key_global'),
    ],

];

