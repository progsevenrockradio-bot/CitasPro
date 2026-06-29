<?php

use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PortafolioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — CitasPro v1
|--------------------------------------------------------------------------
|
| Prefijo global: /api  (definido en RouteServiceProvider)
| Autenticación: Laravel Sanctum (Bearer token)
|
| GRUPOS:
|   [PÚBLICA]    No requieren token
|   [PROTEGIDA]  Requieren header: Authorization: Bearer {token}
|
*/

// ─── Health check ──────────────────────────────────────────────────────────
Route::get('/health', fn () => response()->json([
    'status'    => 'ok',
    'app'       => config('app.name'),
    'version'   => '1.0.0',
    'timestamp' => now()->toIso8601String(),
]))->name('health');

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════════════════════

// ── Autenticación OTP ─────────────────────────────────────────────────────
Route::prefix('auth/otp')->name('auth.otp.')->group(function () {

    /**
     * POST /api/auth/otp/enviar
     * Body: { "telefono": "+34612345678" }
     * Rate limit: 3 intentos / 5 min por número
     */
    Route::post('/enviar', [OtpAuthController::class, 'enviar'])->name('enviar');

    /**
     * POST /api/auth/otp/verificar
     * Body: { "telefono": "+34612345678", "codigo": "123456", "nombre": "Juan" }
     * Devuelve: token Sanctum + datos del cliente
     */
    Route::post('/verificar', [OtpAuthController::class, 'verificar'])->name('verificar');
});

// ── Portafolio público (galería de trabajos del profesional) ──────────────
// Accesible sin autenticación para que clientes puedan ver el trabajo
Route::prefix('portafolio')->name('portafolio.')->group(function () {

    /**
     * GET /api/portafolio/{profesionalId}
     * Query: ?tipo=imagen&destacado=1&per_page=12&servicio_id=5
     */
    Route::get('/{profesionalId}', [PortafolioController::class, 'index'])
        ->name('index')
        ->where('profesionalId', '[0-9]+');
});

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS (requieren: Authorization: Bearer {token})
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Sesión y perfil ───────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {

        /** GET /api/auth/me — Datos del cliente autenticado */
        Route::get('/me', [OtpAuthController::class, 'me'])->name('me');

        /** POST /api/auth/logout — Cierra sesión en este dispositivo */
        Route::post('/logout', [OtpAuthController::class, 'logout'])->name('logout');

        /** POST /api/auth/logout-all — Cierra sesión en todos los dispositivos */
        Route::post('/logout-all', [OtpAuthController::class, 'logoutTodos'])->name('logout.all');
    });

    // ── Dashboard y métricas ──────────────────────────────────────────────
    Route::prefix('dashboard')->name('dashboard.')->group(function () {

        /**
         * GET /api/dashboard/metricas
         * GET /api/dashboard/metricas/{periodo}
         *
         * periodos: mes_actual | mes_anterior | semana | anio
         * Query: ?profesional_id=5
         *
         * Respuesta: métricas completas con comparativa y distribuciones
         */
        Route::get('/metricas', [DashboardController::class, 'metricas'])
            ->name('metricas');

        Route::get('/metricas/{periodo}', [DashboardController::class, 'metricas'])
            ->name('metricas.periodo')
            ->where('periodo', 'mes_actual|mes_anterior|semana|anio');

        /**
         * GET /api/dashboard/agenda
         * Citas de hoy + próximos 7 días del profesional.
         * Query: ?profesional_id=5
         */
        Route::get('/agenda', [DashboardController::class, 'agenda'])
            ->name('agenda');

        /**
         * GET /api/dashboard/resumen-rapido
         * Widget compacto de KPIs para app móvil.
         * Query: ?profesional_id=5
         */
        Route::get('/resumen-rapido', [DashboardController::class, 'resumenRapido'])
            ->name('resumen.rapido');
    });

    // ── Gestión de Portafolio (protegida: solo el profesional o admin) ────
    Route::prefix('portafolio')->name('portafolio.')->group(function () {

        /**
         * POST /api/portafolio/{profesionalId}/subir
         * Content-Type: multipart/form-data
         * Campos: archivo* (imagen/video), titulo, descripcion, servicio_id,
         *         destacado, tipo, archivo_antes
         *
         * Respuesta: { success, portafolio: { id, url, url_miniatura, disco, ... } }
         */
        Route::post('/{profesionalId}/subir', [PortafolioController::class, 'subir'])
            ->name('subir')
            ->where('profesionalId', '[0-9]+');

        /**
         * PATCH /api/portafolio/{id}
         * Body: { titulo, descripcion, servicio_id, destacado, publico, orden }
         */
        Route::patch('/{id}', [PortafolioController::class, 'actualizar'])
            ->name('actualizar')
            ->where('id', '[0-9]+');

        /**
         * DELETE /api/portafolio/{id}
         * Elimina archivo del disco + registro de la BD.
         */
        Route::delete('/{id}', [PortafolioController::class, 'eliminar'])
            ->name('eliminar')
            ->where('id', '[0-9]+');

        /**
         * POST /api/portafolio/reordenar
         * Body: { "orden": [ {"id": 3, "orden": 1}, ... ] }
         * Reordena múltiples entradas en una sola llamada.
         */
        Route::post('/reordenar', [PortafolioController::class, 'reordenar'])
            ->name('reordenar');
    });

    // ── Placeholders para Fase 3 (CRUD Negocios, Citas) ──────────────────
    Route::prefix('v1')->name('v1.')->group(function () {
        Route::get('/negocios',  fn () => response()->json(['message' => 'Próximamente — Fase 3']))->name('negocios');
        Route::get('/citas',     fn () => response()->json(['message' => 'Próximamente — Fase 3']))->name('citas');
        Route::get('/servicios', fn () => response()->json(['message' => 'Próximamente — Fase 3']))->name('servicios');
    });
});


/*
|--------------------------------------------------------------------------
| API Routes — CitasPro
|--------------------------------------------------------------------------
|
| Todas las rutas tienen el prefijo /api (configurado en RouteServiceProvider).
| Protección:
|   - Públicas: No requieren autenticación
|   - Protegidas: Requieren header "Authorization: Bearer {token}"
|
*/

// ─── Estado de la API ───────────────────────────────────────────────────────
Route::get('/health', fn () => response()->json([
    'status'    => 'ok',
    'app'       => config('app.name'),
    'version'   => '1.0.0',
    'timestamp' => now()->toIso8601String(),
]));

// ─── Autenticación OTP (Rutas PÚBLICAS) ────────────────────────────────────
Route::prefix('auth/otp')->name('auth.otp.')->group(function () {
    /**
     * POST /api/auth/otp/enviar
     * Body: { "telefono": "+34612345678" }
     * Genera y envía (simula) el código OTP al número recibido.
     */
    Route::post('/enviar', [OtpAuthController::class, 'enviar'])
        ->name('enviar');

    /**
     * POST /api/auth/otp/verificar
     * Body: { "telefono": "+34612345678", "codigo": "123456", "nombre": "Juan" }
     * Valida el OTP y devuelve el token Sanctum.
     */
    Route::post('/verificar', [OtpAuthController::class, 'verificar'])
        ->name('verificar');
});

// ─── Rutas PROTEGIDAS (requieren token Sanctum) ─────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Autenticación ─────────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        /** GET /api/auth/me — Perfil del cliente autenticado */
        Route::get('/me', [OtpAuthController::class, 'me'])->name('me');

        /** POST /api/auth/logout — Cierra sesión en este dispositivo */
        Route::post('/logout', [OtpAuthController::class, 'logout'])->name('logout');

        /** POST /api/auth/logout-all — Cierra sesión en todos los dispositivos */
        Route::post('/logout-all', [OtpAuthController::class, 'logoutTodos'])->name('logout-all');
    });

    // ── Placeholder: rutas que se implementarán en Fases posteriores ──────
    Route::get('/negocios', fn () => response()->json(['message' => 'Próximamente — Fase 2']))->name('negocios.index');
    Route::get('/citas', fn () => response()->json(['message' => 'Próximamente — Fase 2']))->name('citas.index');
    Route::get('/perfil', fn () => response()->json(['message' => 'Usa /api/auth/me']))->name('perfil');
});
