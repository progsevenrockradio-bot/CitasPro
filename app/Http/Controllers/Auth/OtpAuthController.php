<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMailNotification;
use App\Models\Cliente;
use App\Models\Profesional;
use App\Models\OtpCode;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        // Acepta teléfono O email (al menos uno es obligatorio)
        $request->validate([
            'telefono' => [
                'nullable',
                'string',
                'min:7',
                'max:20',
                'regex:/^\+?[1-9]\d{6,19}$/',    // Formato internacional E.164
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
        ], [
            'telefono.regex' => 'El número debe tener formato internacional, ej: +34612345678.',
            'telefono.min'   => 'El número de celular es demasiado corto.',
            'telefono.max'   => 'El número de celular es demasiado largo.',
            'email.email'    => 'Ingresa un correo electrónico válido.',
        ]);

        // Requiere al menos uno
        if (empty($request->telefono) && empty($request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes proporcionar un número de teléfono o un correo electrónico.',
            ], 422);
        }

        // ── Determinar canal de envío ───────────────────────────
        // El cliente puede mandar 'canal' = 'email', 'telegram' o 'sms'
        $canalSolicitado = $request->input('canal', 'email');
        
        $usarEmail = true;
        $usarTelegram = false;
        $usarSms = false;

        // Validar si el destinatario tiene los datos necesarios para el canal
        if ($canalSolicitado === 'telegram') {
            // Verificamos si existe el usuario con ese email/teléfono y si tiene chat_id
            $chatId = null;
            if ($request->email) {
                $chatId = Profesional::where('email', $request->email)->value('telegram_chat_id') 
                       ?? Cliente::where('email', $request->email)->value('telegram_chat_id');
            }
            if (!$chatId && $request->telefono) {
                $telefonoLimpio = $this->normalizarTelefono($request->telefono);
                $chatId = Profesional::where('telefono', $telefonoLimpio)->value('telegram_chat_id') 
                       ?? Cliente::where('telefono', $telefonoLimpio)->value('telegram_chat_id');
            }

            if ($chatId) {
                $usarTelegram = true;
                $usarEmail = false;
            } else {
                // Fallback a Email si no tiene Telegram configurado
                $usarEmail = true;
            }
        } elseif ($canalSolicitado === 'sms' && !empty($request->telefono)) {
            $usarSms = true;
            $usarEmail = false;
        }

        // Definición de destinatario principal según canal activo
        if ($usarEmail) {
            $destinatario = strtolower(trim($request->email ?? ''));
            // Si el login fue con teléfono pero el canal es email, intentamos buscar su email
            if (empty($destinatario) && $request->telefono) {
                $tel = $this->normalizarTelefono($request->telefono);
                $destinatario = Profesional::where('telefono', $tel)->value('email')
                             ?? Cliente::where('telefono', $tel)->value('email');
            }
            // Fallback en caso extremo
            if (empty($destinatario)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un correo electrónico vinculado para este usuario.',
                ], 422);
            }
            $rateLimiterKey = "otp_enviar:email:{$destinatario}";
        } elseif ($usarTelegram) {
            $destinatario = $request->email ? strtolower(trim($request->email)) : $this->normalizarTelefono($request->telefono);
            $rateLimiterKey = "otp_enviar:telegram:{$destinatario}";
        } else {
            $destinatario = $this->normalizarTelefono($request->telefono);
            $rateLimiterKey = "otp_enviar:tel:{$destinatario}";
        }

        // Bloquear login passwordless directo por OTP para profesionales con contraseña
        $profesionalExistente = null;
        if (!empty($request->email)) {
            $profesionalExistente = Profesional::where('email', strtolower(trim($request->email)))->first();
        }
        if (!$profesionalExistente && !empty($request->telefono)) {
            $profesionalExistente = Profesional::where('telefono', $this->normalizarTelefono($request->telefono))->first();
        }
        if ($profesionalExistente && !empty($profesionalExistente->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta requiere iniciar sesión con contraseña.',
            ], 403);
        }

        // ── Rate Limiting ───────────────────────────────────────
        if (RateLimiter::tooManyAttempts($rateLimiterKey, maxAttempts: 3)) {
            $segundos = RateLimiter::availableIn($rateLimiterKey);

            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Inténtalo de nuevo en {$segundos} segundos.",
                'retry_after_seconds' => $segundos,
            ], 429);
        }

        RateLimiter::hit($rateLimiterKey, decaySeconds: 300); // 5 minutos

        // ── Generar y guardar OTP ───────────────────────────────
        if ($usarEmail) {
            $otp = OtpCode::crearParaEmail(
                email: $destinatario,
                tipo: 'login',
                ip: $request->ip()
            );
        } else {
            $otp = OtpCode::crearParaTelefono(
                telefono: $usarSms ? $destinatario : null,
                tipo: 'login',
                ip: $request->ip()
            );
            // Si es telegram guardamos también el email si existía
            if ($usarTelegram && $request->email) {
                $otp->update(['email' => strtolower(trim($request->email))]);
            }
        }

        // ── Enviar OTP ──────────────────────────────────────────
        $enviado = false;
        $enviadoPorTelegram = false;

        if ($usarEmail) {
            $enviado = $this->enviarOtpPorEmail($destinatario, $otp->plain_codigo);
        } elseif ($usarTelegram) {
            $enviadoPorTelegram = $this->enviarOtpPorTelegram($destinatario, $otp->plain_codigo, !empty($request->email));
            $enviado = $enviadoPorTelegram;
        } else {
            $enviado = $this->enviarOtp($destinatario, $otp->plain_codigo);
            // También intentamos enviar por telegram de respaldo si tiene
            $enviadoPorTelegram = $this->enviarOtpPorTelegram($destinatario, $otp->plain_codigo, false);
        }

        if (!$enviado && !$enviadoPorTelegram) {
            Log::error("OtpAuthController: Fallo total al enviar OTP a {$destinatario}");
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al enviar el código de verificación.',
            ], 500);
        }

        // ── Respuesta ───────────────────────────────────────────
        $canal = $usarEmail ? 'email' : ($usarTelegram ? 'telegram' : 'telefono');
        $destinatarioMostrar = $usarEmail
            ? $this->enmascararEmail($destinatario)
            : ($usarTelegram ? 'Telegram' : $this->enmascararTelefono($destinatario));

        $canales = [];
        if ($usarEmail) $canales[] = 'email';
        if ($usarSms) $canales[] = 'sms';
        if ($enviadoPorTelegram || $usarTelegram) $canales[] = 'telegram';

        $response = [
            'success'           => true,
            'canal'             => $canal,
            'canales_enviados'  => $canales,
            'message'           => $usarTelegram 
                ? "Código de verificación enviado por Telegram."
                : ($enviadoPorTelegram 
                    ? "Código enviado a {$destinatarioMostrar} y también por Telegram."
                    : "Código de verificación enviado a {$destinatarioMostrar}."),
            'expira_en_minutos' => OtpCode::DURACION_MINUTOS,
        ];

        // ⚠️ SOLO en entorno local/testing se expone el código (facilita desarrollo)
        if (app()->environment(['local', 'testing'])) {
            $response['_debug_codigo'] = $otp->plain_codigo;
            $response['_debug_aviso']  = 'Este campo solo aparece en entorno local o testing.';
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
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{6,19}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'codigo' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
            ],
            'nombre'          => ['sometimes', 'string', 'max:100'],
            'apellido'        => ['sometimes', 'string', 'max:100'],
            'nombre_token'    => ['sometimes', 'string', 'max:100'],
            'challenge_token' => ['nullable', 'string', 'max:128'],
        ], [
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.size'     => 'El código debe tener exactamente 6 dígitos.',
            'codigo.regex'    => 'El código debe ser numérico.',
        ]);

        if (empty($request->telefono) && empty($request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes proporcionar un número de teléfono o un correo electrónico.',
            ], 422);
        }

        $usarEmail = !empty($request->email);
        $codigo    = $request->codigo;

        if ($usarEmail) {
            $identificador = strtolower(trim($request->email));
        } else {
            $identificador = $this->normalizarTelefono($request->telefono);
        }

        // ── Rate Limiting anti-brute-force ──────────────────────
        $rateLimiterKey = $usarEmail
            ? "otp_verificar:email:{$identificador}"
            : "otp_verificar:tel:{$identificador}";

        if (RateLimiter::tooManyAttempts($rateLimiterKey, maxAttempts: 5)) {
            $segundos = RateLimiter::availableIn($rateLimiterKey);

            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos fallidos. Espera {$segundos} segundos.",
                'retry_after_seconds' => $segundos,
            ], 429);
        }

        $telefono = $usarEmail ? null : $identificador;
        $telefonoContacto = $telefono ?: ($request->filled('telefono') ? $this->normalizarTelefono($request->telefono) : null);

        if ($request->boolean('es_registro')) {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'email' => 'required|email|unique:profesionales,email',
                'password' => 'required|string|min:6',
                'nombre_negocio' => 'required|string|max:255',
                'categoria_id' => 'required|exists:categorias,id',
            ], [
                'email.unique' => 'Ese correo electrónico ya está registrado.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.'
            ]);

            if ($telefonoContacto && Profesional::where('telefono', $telefonoContacto)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este número de teléfono ya está registrado como profesional.',
                ], 422);
            }
        }

        // ── Verificación atómica, desafío 2FA y registro conjunto ─
        try {
            $resultado = DB::transaction(function () use ($request, $usarEmail, $identificador, $codigo, $telefono, $telefonoContacto) {
                $query = OtpCode::vigente()->latest('id');
                if ($usarEmail) {
                    $query->paraEmail($identificador);
                } else {
                    $query->paraTelefono($identificador);
                }
                $otpRecord = $query->lockForUpdate()->first();

                if (!$otpRecord) {
                    return ['ok' => false, 'status' => 422, 'reason' => 'not_found'];
                }

                if (!$otpRecord->verificarCodigo($codigo)) {
                    $otpRecord->registrarIntentoFallido();
                    $intentosRestantes = max(0, OtpCode::MAX_INTENTOS - $otpRecord->fresh()->intentos);
                    return ['ok' => false, 'status' => 422, 'reason' => 'invalid', 'intentos_restantes' => $intentosRestantes];
                }

                $profesional = $telefono
                    ? Profesional::where('telefono', $telefono)->first()
                    : Profesional::where('email', $identificador)->first();

                if (!$request->boolean('es_registro') && $profesional && !empty($profesional->password)) {
                    if ($otpRecord->tipo !== '2fa' || !$otpRecord->verificarChallenge($request->input('challenge_token'), $profesional->id)) {
                        return ['ok' => false, 'status' => 403, 'reason' => 'challenge_required'];
                    }
                }

                $consumido = OtpCode::where('id', $otpRecord->id)
                    ->where('usado', false)
                    ->where('intentos', '<', OtpCode::MAX_INTENTOS)
                    ->where('expira_en', '>', now())
                    ->update(['usado' => true, 'usado_en' => now()]);

                if ($consumido !== 1) {
                    return ['ok' => false, 'status' => 422, 'reason' => 'not_found'];
                }

                if ($request->boolean('es_registro')) {
                    $negocio = \App\Models\Negocio::create([
                        'nombre' => $request->nombre_negocio,
                        'slug' => \Illuminate\Support\Str::slug($request->nombre_negocio . '-' . uniqid()),
                        'categoria_id' => $request->categoria_id,
                        'telefono' => $telefonoContacto,
                        'email' => $usarEmail ? $identificador : $request->email,
                        'activo' => true,
                    ]);

                    $profesional = Profesional::create([
                        'negocio_id' => $negocio->id,
                        'nombre' => $request->nombre,
                        'apellido' => $request->apellido,
                        'email' => $usarEmail ? $identificador : strtolower(trim($request->email)),
                        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                        'telefono' => $telefonoContacto,
                        'rol' => 'dueño',
                        'activo' => true,
                    ]);
                }

                return ['ok' => true, 'profesional' => $profesional];
            });
        } catch (\Exception $e) {
            Log::error("Error en verificación OTP / registro: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.',
            ], 500);
        }

        if (!$resultado['ok']) {
            RateLimiter::hit($rateLimiterKey, decaySeconds: 300);

            if ($resultado['reason'] === 'challenge_required') {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión con tu contraseña y usar el desafío 2FA vigente.',
                ], 403);
            }

            if ($resultado['reason'] === 'invalid') {
                return response()->json([
                    'success'            => false,
                    'message'            => 'Código incorrecto.',
                    'intentos_restantes' => $resultado['intentos_restantes'],
                    'codigo'             => 'OTP_INVALIDO',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'No existe un código válido. Solicita uno nuevo.',
                'codigo'  => 'OTP_NO_ENCONTRADO',
            ], 422);
        }

        RateLimiter::clear($rateLimiterKey);
        RateLimiter::clear($usarEmail ? "otp_enviar:email:{$identificador}" : "otp_enviar:tel:{$identificador}");

        $profesional = $resultado['profesional'];
        $user = null;
        $role = 'cliente';
        $esNuevoCliente = false;

        if ($profesional) {

            $user = $profesional;
            $role = 'profesional';
            
            $nombreToken = $request->nombre_token ?? 'api-token-prof-' . now()->timestamp;
            $token = $user->createToken(
                name: $nombreToken,
                abilities: ['profesional'],
                expiresAt: now()->addDays(30)
            );

            Log::info("OtpAuthController: Login exitoso PROFESIONAL para {$identificador}", [
                'profesional_id' => $user->id,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => '¡Bienvenido al panel de CitasPro!',
                'role'       => $role,
                'token'      => $token->plainTextToken,
                'token_tipo' => 'Bearer',
                'expira_en'  => now()->addDays(30)->toIso8601String(),
                'user'       => [
                    'id'               => $user->id,
                    'nombre'           => $user->nombre,
                    'apellido'         => $user->apellido,
                    'telefono'         => $user->telefono,
                    'email'            => $user->email,
                    'foto'             => $user->foto,
                ],
            ], 200);
        }

        // ── 2. Crear o actualizar cliente ───────────────────────
        // Buscar por teléfono o por email según canal
        $cliente = $telefono
            ? Cliente::where('telefono', $telefono)->first()
            : Cliente::where('email', $identificador)->first();

        if (!$cliente) {
            $esNuevoCliente = true;
            $cliente = Cliente::create([
                'nombre'                 => $request->nombre ?? 'Cliente',
                'apellido'               => $request->apellido ?? '',
                'telefono'               => $telefono,
                'email'                  => $usarEmail ? $identificador : $request->email,
                'activo'                 => true,
                'telefono_verificado_en' => $telefono ? now() : null,
                'email_verificado_en'    => $usarEmail ? now() : null,
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

        $user = $cliente;

        // ── Emitir token Sanctum ────────────────────────────────
        $nombreToken = $request->nombre_token ?? 'api-token-cliente-' . now()->timestamp;

        $token = $user->createToken(
            name: $nombreToken,
            abilities: ['cliente'],
            expiresAt: now()->addDays(30)
        );

        Log::info("OtpAuthController: Login exitoso CLIENTE para {$identificador}", [
            'cliente_id'    => $user->id,
            'nuevo_cliente' => $esNuevoCliente,
        ]);

        return response()->json([
            'success'        => true,
            'message'        => $esNuevoCliente ? '¡Bienvenido a CitasPro!' : '¡Bienvenido de vuelta!',
            'nuevo_cliente'  => $esNuevoCliente,
            'role'           => $role,
            'token'          => $token->plainTextToken,
            'token_tipo'     => 'Bearer',
            'expira_en'      => now()->addDays(30)->toIso8601String(),
            'user'           => [
                'id'               => $user->id,
                'nombre'           => $user->nombre,
                'apellido'         => $user->apellido,
                'nombre_completo'  => $user->nombre_completo,
                'telefono'         => $user->telefono,
                'email'            => $user->email,
                'foto'             => $user->foto,
                'telefono_verificado' => (bool) $user->telefono_verificado_en,
            ],
            // Compatibilidad con frontend antiguo (por si acaso):
            'cliente'        => [
                'id'               => $user->id,
                'nombre'           => $user->nombre,
                'apellido'         => $user->apellido,
                'nombre_completo'  => $user->nombre_completo,
                'telefono'         => $user->telefono,
                'email'            => $user->email,
                'foto'             => $user->foto,
                'telefono_verificado' => (bool) $user->telefono_verificado_en,
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
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

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

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Si el usuario es un Súper Admin (modelo User)
        if ($user instanceof \App\Models\User) {
            return response()->json([
                'success' => true,
                'role'    => 'superadmin',
                'user'    => [
                    'id'                => $user->id,
                    'nombre'            => $user->name,
                    'apellido'          => '',
                    'email'             => $user->email,
                    'rol'               => 'superadmin',
                    'type'              => 'superadmin',
                ],
            ], 200);
        }

        // Si el usuario es un Profesional
        if ($user instanceof Profesional) {
            return response()->json([
                'success' => true,
                'role'    => 'profesional',
                'user'    => [
                    'id'                => $user->id,
                    'nombre'            => $user->nombre,
                    'apellido'          => $user->apellido,
                    'telefono'          => $user->telefono,
                    'email'             => $user->email,
                    'foto'              => $user->foto,
                    'rol'               => $user->rol,
                    'activo'            => $user->activo,
                    'type'              => $user->type,
                    'telegram_chat_id'  => $user->telegram_chat_id,
                    'telegram_bot_username' => config('services.telegram.bot_username', 'CitasProAlertsBot'),
                    'google_calendar_connected' => !empty($user->google_calendar_token),
                    'created_at'        => $user->created_at->toIso8601String(),
                ],
            ], 200);
        }

        // Si el usuario es un Cliente
        return response()->json([
            'success' => true,
            'role'    => 'cliente',
            'user' => [
                'id'                    => $user->id,
                'nombre'                => $user->nombre,
                'apellido'              => $user->apellido,
                'nombre_completo'       => $user->nombre_completo,
                'telefono'              => $user->telefono,
                'email'                 => $user->email,
                'foto'                  => $user->foto,
                'fecha_nacimiento'      => $user->fecha_nacimiento?->toDateString(),
                'genero'                => $user->genero,
                'pais'                  => $user->pais,
                'acepta_marketing'      => $user->acepta_marketing,
                'telefono_verificado'   => $user->telefonoVerificado(),
                'created_at'            => $user->created_at->toIso8601String(),
                'total_citas'           => clone $user->citas()->count(),
            ],
            // Para mantener compatibilidad si algo del frontend lo usaba directamente:
            'cliente' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
            ]
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ─────────────────────────────────────────────────────────────

    /**
     * Normaliza el número de teléfono al formato E.164.
     */
    private function normalizarTelefono(string $telefono): string
    {
        $telefono = preg_replace('/[^\d+]/', '', $telefono);

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
     * Enmascara el email para mostrarlo en mensajes de respuesta.
     * Ej: jmfont@gmail.com → j*****t@gmail.com
     */
    private function enmascararEmail(string $email): string
    {
        [$local, $dominio] = explode('@', $email, 2);
        $longitud = strlen($local);
        if ($longitud <= 2) {
            return $local . '@' . $dominio;
        }
        return substr($local, 0, 1)
            . str_repeat('*', max(1, $longitud - 2))
            . substr($local, -1)
            . '@' . $dominio;
    }

    /**
     * Envía el OTP por SMS/WhatsApp al teléfono.
     * En producción integrar WhatsApp Cloud API o Twilio.
     *
     * @return bool true si se envió correctamente
     */
    private function enviarOtp(string $telefono, string $codigo): bool
    {
        // ── MODO SIMULACIÓN ─────────────────────────────────────
        if (app()->environment(['local', 'testing']) || config('app.otp_simular', false)) {
            Log::channel('stack')->info("📱 [OTP SIMULADO/SMS] Para: {$telefono} | Código: {$codigo} | Expira: " . now()->addMinutes(OtpCode::DURACION_MINUTOS)->format('H:i'));
            return true;
        }

        // ── PRODUCCIÓN: WhatsApp Cloud API ──────────────────────
        // TODO Fase 5: Implementar integración real
        // return app(\App\Services\WhatsAppService::class)->enviarOtp($telefono, $codigo);

        return true;
    }

    /**
     * Envía el OTP por correo electrónico usando Laravel Mail.
     *
     * @return bool true si se envió correctamente
     */
    private function enviarOtpPorEmail(string $email, string $codigo): bool
    {
        // ── MODO SIMULACIÓN ─────────────────────────────────────
        if (app()->environment(['local', 'testing'])) {
            Log::channel('stack')->info("📧 [OTP SIMULADO/EMAIL] Para: {$email} | Código: {$codigo} | Expira: " . now()->addMinutes(OtpCode::DURACION_MINUTOS)->format('H:i'));
            return true;
        }

        // ── PRODUCCIÓN: Enviar email real ───────────────────────
        try {
            Mail::to($email)->send(new OtpMailNotification(
                codigo: $codigo,
                expiraMinutos: OtpCode::DURACION_MINUTOS,
                nombreUsuario: 'Usuario'
            ));

            Log::info("📧 OTP enviado por email a: {$email}");
            return true;
        } catch (\Exception $e) {
            Log::error("OtpAuthController: Error enviando OTP por email a {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía el OTP por Telegram si el usuario tiene telegram_chat_id registrado.
     *
     * Busca en Profesionales y Clientes por el identificador (teléfono o email).
     * Si encuentra un chat_id, envía el PIN via el bot de CitasPro.
     *
     * @return bool true si se encontró chat_id y se envió el mensaje
     */
    private function enviarOtpPorTelegram(string $identificador, string $codigo, bool $esEmail = false): bool
    {
        // Buscar el telegram_chat_id del usuario en Profesionales
        $query = $esEmail
            ? Profesional::where('email', $identificador)
            : Profesional::where('telefono', $identificador);

        $chatId = $query->value('telegram_chat_id');

        // Si no es profesional, buscar en Clientes
        if (!$chatId) {
            $queryCliente = $esEmail
                ? Cliente::where('email', $identificador)
                : Cliente::where('telefono', $identificador);

            $chatId = $queryCliente->value('telegram_chat_id');
        }

        if (!$chatId) {
            return false; // Usuario no tiene Telegram vinculado
        }

        $expiraMinutos = OtpCode::DURACION_MINUTOS;

        // ── MODO SIMULACIÓN ─────────────────────────────────────
        if (app()->environment(['local', 'testing'])) {
            Log::channel('stack')->info("🤖 [OTP SIMULADO/TELEGRAM] Para chat_id: {$chatId} | Código: {$codigo}");
            return true;
        }

        // ── PRODUCCIÓN: Enviar via Bot de Telegram ──────────────
        try {
            $telegram = app(TelegramService::class);

            $mensaje = "🔐 <b>Código de acceso CitasPro</b>\n\n"
                . "Tu código de verificación es:\n\n"
                . "<code><b>{$codigo}</b></code>\n\n"
                . "⏱ Válido por <b>{$expiraMinutos} minutos</b>.\n\n"
                . "⚠️ <i>Si no solicitaste este código, ignora este mensaje.</i>";

            return $telegram->enviarMensaje(chatId: (string) $chatId, mensaje: $mensaje);
        } catch (\Exception $e) {
            Log::error("OtpAuthController: Error enviando OTP por Telegram (chat_id: {$chatId}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * PATCH /api/auth/perfil
     * 
     * Permite al profesional actualizar su propia contraseña,
     * estado de 2FA y el canal preferido para recibir el PIN.
     */
    public function updatePerfil(Request $request): JsonResponse
    {
        $profesional = $request->user();
        if (!$profesional || !($profesional instanceof Profesional)) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $validated = $request->validate([
            'nombre'              => 'sometimes|string|max:100',
            'apellido'            => 'sometimes|string|max:100',
            'telefono'            => 'sometimes|nullable|string|max:20',
            'doble_factor_activo' => 'sometimes|boolean',
            'canal_preferido_2fa' => 'sometimes|in:email,telegram',
            'password'            => 'sometimes|nullable|string|min:6|confirmed', // password_confirmation es requerido si se envía password
        ], [
            'password.min'        => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'  => 'La confirmación de la contraseña no coincide.',
            'canal_preferido_2fa.in' => 'El canal preferido debe ser email o telegram.'
        ]);

        // Manejo de la contraseña
        if (!empty($request->password)) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $profesional->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Perfil y configuración de seguridad actualizados correctamente.',
            'user' => [
                'id'                => $profesional->id,
                'nombre'            => $profesional->nombre,
                'apellido'          => $profesional->apellido,
                'email'             => $profesional->email,
                'telefono'          => $profesional->telefono,
                'doble_factor_activo' => (bool) $profesional->doble_factor_activo,
                'canal_preferido_2fa' => $profesional->canal_preferido_2fa,
            ]
        ]);
    }

    /**
     * POST /api/auth/login-contrasena
     * 
     * Login inicial por correo y contraseña.
     * Si el 2FA está activo, envía un código OTP y retorna requiere_2fa => true.
     * De lo contrario, emite el token Sanctum directamente.
     */
    public function loginContrasena(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $profesional = Profesional::where('email', strtolower(trim($request->email)))->first();

        // Si no existe o contraseña incorrecta
        if (!$profesional || !\Illuminate\Support\Facades\Hash::check($request->password, $profesional->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Comprobar si tiene el doble factor activo
        if ($profesional->doble_factor_activo) {
            $canal = $profesional->canal_preferido_2fa ?? 'email';
            $plainChallenge = \Illuminate\Support\Str::random(64);
            $otp = OtpCode::crearParaEmail(
                email: $profesional->email,
                tipo: '2fa',
                ip: $request->ip(),
                challengeHash: OtpCode::hashValor($plainChallenge),
                profesionalId: $profesional->id
            );

            $enviado = false;
            if ($canal === 'telegram' && $profesional->telegram_chat_id) {
                $enviado = $this->enviarOtpPorTelegram($profesional->email, $otp->plain_codigo, true);
            }

            // Fallback a correo si falla telegram o el canal es email
            if (!$enviado) {
                $enviado = $this->enviarOtpPorEmail($profesional->email, $otp->plain_codigo);
                $canal = 'email';
            }

            return response()->json([
                'success' => true,
                'requiere_2fa' => true,
                'challenge_token' => $plainChallenge,
                'canal' => $canal,
                'destinatario' => $canal === 'email'
                    ? $this->enmascararEmail($profesional->email)
                    : 'Telegram Bot',
                'message' => 'Se requiere código de verificación de dos pasos (2FA).',
            ]);
        }

        // Si no requiere 2FA, iniciamos sesión directamente emitiendo el token
        $token = $profesional->createToken(
            name: 'api-token-prof-' . now()->timestamp,
            abilities: ['profesional'],
            expiresAt: now()->addDays(30)
        );

        return response()->json([
            'success' => true,
            'requiere_2fa' => false,
            'token' => $token->plainTextToken,
            'token_tipo' => 'Bearer',
            'expira_en' => now()->addDays(30)->toIso8601String(),
            'user' => [
                'id' => $profesional->id,
                'nombre' => $profesional->nombre,
                'apellido' => $profesional->apellido,
                'telefono' => $profesional->telefono,
                'email' => $profesional->email,
                'foto' => $profesional->foto,
            ]
        ], 200);
    }
}
