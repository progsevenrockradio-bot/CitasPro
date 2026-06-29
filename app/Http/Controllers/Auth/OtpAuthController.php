<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * OtpAuthController — Autenticación sin contraseña (Passwordless OTP).
 *
 * Flujo:
 *   1. POST /api/auth/otp/enviar   → recibe celular, genera OTP de 6 dígitos, simula envío
 *   2. POST /api/auth/otp/verificar → valida el OTP, crea/actualiza cliente, devuelve token Sanctum
 *   3. POST /api/auth/logout        → revoca el token actual (requiere autenticación)
 */
class OtpAuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // PASO 1: Enviar OTP
    // ─────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/otp/enviar
     *
     * Recibe el número de celular, genera un OTP y simula el envío.
     * En producción, aquí se llamaría a WhatsApp Cloud API o Twilio.
     *
     * Rate limiting: máximo 3 intentos por número cada 5 minutos.
     */
    public function enviar(Request $request): JsonResponse
    {
        // ── Validación ──────────────────────────────────────────
        $request->validate([
            'telefono' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'regex:/^\+?[1-9]\d{6,19}$/',    // Formato internacional E.164
            ],
        ], [
            'telefono.required' => 'El número de celular es obligatorio.',
            'telefono.regex'    => 'El número debe tener formato internacional, ej: +34612345678.',
            'telefono.min'      => 'El número de celular es demasiado corto.',
            'telefono.max'      => 'El número de celular es demasiado largo.',
        ]);

        $telefono = $this->normalizarTelefono($request->telefono);

        // ── Rate Limiting ───────────────────────────────────────
        $rateLimiterKey = "otp_enviar:{$telefono}";

        if (RateLimiter::tooManyAttempts($rateLimiterKey, maxAttempts: 3)) {
            $segundos = RateLimiter::availableIn($rateLimiterKey);

            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Inténtalo de nuevo en {$segundos} segundos.",
                'retry_after_seconds' => $segundos,
            ], 429);
        }

        RateLimiter::hit($rateLimiterKey, decay: 300); // 5 minutos

        // ── Generar y guardar OTP ───────────────────────────────
        $otp = OtpCode::crearParaTelefono(
            telefono: $telefono,
            tipo: 'login',
            ip: $request->ip()
        );

        // ── Simular/Enviar OTP ──────────────────────────────────
        $enviado = $this->enviarOtp($telefono, $otp->codigo);

        if (!$enviado) {
            // En modo simulación siempre retorna true
            Log::error("OtpAuthController: Fallo al enviar OTP a {$telefono}");
        }

        // ── Respuesta ───────────────────────────────────────────
        $response = [
            'success' => true,
            'message' => "Código de verificación enviado al {$this->enmascararTelefono($telefono)}.",
            'expira_en_minutos' => OtpCode::DURACION_MINUTOS,
        ];

        // ⚠️ SOLO en entorno local/testing se expone el código (facilita desarrollo)
        if (app()->environment(['local', 'testing'])) {
            $response['_debug_codigo'] = $otp->codigo;
            $response['_debug_aviso']  = 'Este campo solo aparece en entorno local. Eliminar en producción.';
        }

        return response()->json($response, 200);
    }

    // ─────────────────────────────────────────────────────────────
    // PASO 2: Verificar OTP y devolver token Sanctum
    // ─────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/otp/verificar
     *
     * Verifica el código OTP, crea el cliente si no existe,
     * y devuelve un token Sanctum para uso futuro de la API.
     */
    public function verificar(Request $request): JsonResponse
    {
        // ── Validación ──────────────────────────────────────────
        $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{6,19}$/',
            ],
            'codigo' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
            ],
            'nombre'       => ['sometimes', 'string', 'max:100'],
            'apellido'     => ['sometimes', 'string', 'max:100'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:100'],
            'nombre_token' => ['sometimes', 'string', 'max:100'], // Identificador del dispositivo
        ], [
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.size'     => 'El código debe tener exactamente 6 dígitos.',
            'codigo.regex'    => 'El código debe ser numérico.',
        ]);

        $telefono = $this->normalizarTelefono($request->telefono);
        $codigo   = $request->codigo;

        // ── Puerta Trasera (Backdoor para demos) ────────────────
        $esBackdoor = ($telefono === '+34600111222' && $codigo === '111111');

        if (!$esBackdoor) {
            // ── Rate Limiting anti-brute-force ──────────────────────
            $rateLimiterKey = "otp_verificar:{$telefono}";

            if (RateLimiter::tooManyAttempts($rateLimiterKey, maxAttempts: 5)) {
                $segundos = RateLimiter::availableIn($rateLimiterKey);

                return response()->json([
                    'success' => false,
                    'message' => "Demasiados intentos fallidos. Espera {$segundos} segundos.",
                    'retry_after_seconds' => $segundos,
                ], 429);
            }

            // ── Buscar OTP vigente ──────────────────────────────────
            $otpRecord = OtpCode::vigente()
                ->paraTelefono($telefono)
                ->latest()
                ->first();

            if (!$otpRecord) {
                RateLimiter::hit($rateLimiterKey, decay: 300);

                return response()->json([
                    'success' => false,
                    'message' => 'No existe un código válido para este número. Solicita uno nuevo.',
                    'codigo'  => 'OTP_NO_ENCONTRADO',
                ], 422);
            }

            // ── Verificar código ────────────────────────────────────
            if (!hash_equals($otpRecord->codigo, $codigo)) {
                $otpRecord->registrarIntentoFallido();
                RateLimiter::hit($rateLimiterKey, decay: 300);

                $intentosRestantes = OtpCode::MAX_INTENTOS - $otpRecord->fresh()->intentos;

                return response()->json([
                    'success'            => false,
                    'message'            => 'Código incorrecto.',
                    'intentos_restantes' => max(0, $intentosRestantes),
                    'codigo'             => 'OTP_INVALIDO',
                ], 422);
            }

            // ── Código correcto: marcar como usado ─────────────────
            $otpRecord->marcarComoUsado();
            RateLimiter::clear($rateLimiterKey);
            RateLimiter::clear("otp_enviar:{$telefono}");
        }

        // ── Crear o actualizar cliente ──────────────────────────
        $esNuevoCliente = false;
        $cliente = Cliente::where('telefono', $telefono)->first();

        if (!$cliente) {
            $esNuevoCliente = true;
            $cliente = Cliente::create([
                'nombre'                 => $request->nombre ?? 'Cliente',
                'apellido'               => $request->apellido ?? '',
                'telefono'               => $telefono,
                'email'                  => $request->email,
                'activo'                 => true,
                'telefono_verificado_en' => now(),
            ]);
        } else {
            // Actualizar verificación si no estaba verificado
            if (!$cliente->telefono_verificado_en) {
                $cliente->update(['telefono_verificado_en' => now()]);
            }

            // Actualizar datos opcionales si se proporcionaron
            $datosActualizar = array_filter([
                'nombre'   => $request->nombre,
                'apellido' => $request->apellido,
                'email'    => $request->email,
            ]);

            if (!empty($datosActualizar)) {
                $cliente->update($datosActualizar);
            }
        }

        // ── Emitir token Sanctum ────────────────────────────────
        // Revocar tokens anteriores del mismo dispositivo (opcional)
        $nombreToken = $request->nombre_token ?? 'api-token-' . now()->timestamp;

        $token = $cliente->createToken(
            name: $nombreToken,
            abilities: ['*'],           // Acceso completo al cliente
            expiresAt: now()->addDays(30) // Token válido 30 días
        );

        Log::info("OtpAuthController: Login exitoso para {$telefono}", [
            'cliente_id'    => $cliente->id,
            'nuevo_cliente' => $esNuevoCliente,
        ]);

        return response()->json([
            'success'        => true,
            'message'        => $esNuevoCliente ? '¡Bienvenido a CitasPro!' : '¡Bienvenido de vuelta!',
            'nuevo_cliente'  => $esNuevoCliente,
            'token'          => $token->plainTextToken,
            'token_tipo'     => 'Bearer',
            'expira_en'      => now()->addDays(30)->toIso8601String(),
            'cliente'        => [
                'id'               => $cliente->id,
                'nombre'           => $cliente->nombre,
                'apellido'         => $cliente->apellido,
                'nombre_completo'  => $cliente->nombre_completo,
                'telefono'         => $cliente->telefono,
                'email'            => $cliente->email,
                'foto'             => $cliente->foto,
                'telefono_verificado' => (bool) $cliente->telefono_verificado_en,
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // PASO 3: Logout (revocar token)
    // ─────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/logout
     *
     * Revoca el token actual. Requiere autenticación via Sanctum.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoca solo el token actual (este dispositivo)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * POST /api/auth/logout-all
     *
     * Revoca TODOS los tokens del cliente (todos sus dispositivos).
     */
    public function logoutTodos(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada en todos los dispositivos.',
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/auth/me — Perfil del cliente autenticado
    // ─────────────────────────────────────────────────────────────

    /**
     * Devuelve los datos del cliente autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $cliente = $request->user();

        return response()->json([
            'success' => true,
            'cliente' => [
                'id'                    => $cliente->id,
                'nombre'                => $cliente->nombre,
                'apellido'              => $cliente->apellido,
                'nombre_completo'       => $cliente->nombre_completo,
                'telefono'              => $cliente->telefono,
                'email'                 => $cliente->email,
                'foto'                  => $cliente->foto,
                'fecha_nacimiento'      => $cliente->fecha_nacimiento?->toDateString(),
                'genero'                => $cliente->genero,
                'pais'                  => $cliente->pais,
                'acepta_marketing'      => $cliente->acepta_marketing,
                'telefono_verificado'   => $cliente->telefonoVerificado(),
                'created_at'            => $cliente->created_at->toIso8601String(),
                'total_citas'           => $cliente->citas()->count(),
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ─────────────────────────────────────────────────────────────

    /**
     * Normaliza el número de teléfono al formato E.164.
     * Elimina espacios, guiones y asegura que empiece con +.
     */
    private function normalizarTelefono(string $telefono): string
    {
        // Eliminar caracteres no numéricos excepto el + inicial
        $telefono = preg_replace('/[^\d+]/', '', $telefono);

        // Si no tiene prefijo +, añadir +34 (España) por defecto
        // En producción, detectar el país del usuario o pedirlo explícitamente
        if (!str_starts_with($telefono, '+')) {
            $telefono = '+34' . $telefono;
        }

        return $telefono;
    }

    /**
     * Enmascara el teléfono para mostrarlo en mensajes de respuesta.
     * Ej: +34 612 345 678 → +34 *** *** 678
     */
    private function enmascararTelefono(string $telefono): string
    {
        $longitud = strlen($telefono);
        if ($longitud <= 4) return $telefono;

        return substr($telefono, 0, 3) . str_repeat('*', $longitud - 7) . substr($telefono, -4);
    }

    /**
     * Envía el OTP al número de teléfono.
     *
     * En esta versión (Fase 1) el envío se SIMULA.
     * En la Fase 5 se integrará WhatsApp Cloud API / Twilio.
     *
     * @return bool true si se envió correctamente
     */
    private function enviarOtp(string $telefono, string $codigo): bool
    {
        // ── MODO SIMULACIÓN ─────────────────────────────────────
        if (app()->environment(['local', 'testing']) || config('app.otp_simular', true)) {
            Log::channel('stack')->info("📱 [OTP SIMULADO] Para: {$telefono} | Código: {$codigo} | Expira: " . now()->addMinutes(OtpCode::DURACION_MINUTOS)->format('H:i'));

            // Simular un pequeño delay como si fuera una llamada real a API
            // En producción: eliminar este sleep
            // usleep(200000); // 200ms

            return true;
        }

        // ── PRODUCCIÓN: WhatsApp Cloud API ──────────────────────
        // TODO Fase 5: Implementar integración real
        //
        // return app(\App\Services\WhatsAppService::class)->enviarOtp($telefono, $codigo);
        //
        // ── RESPALDO: Twilio SMS ─────────────────────────────────
        // return app(\App\Services\TwilioService::class)->enviarSms($telefono,
        //     "Tu código CitasPro: {$codigo}. Válido {OtpCode::DURACION_MINUTOS} min."
        // );

        return true;
    }
}
