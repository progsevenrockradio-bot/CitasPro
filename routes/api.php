<?php

use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PortafolioController;
use App\Http\Controllers\Api\TelegramBotController;
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

// ── Webhook de Telegram ──────────────────────────────────────────────────
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle'])->name('telegram.webhook');

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

        /**
         * PATCH /api/dashboard/citas/{id}/estado
         * Actualizar estado de cita.
         */
        Route::patch('/citas/{id}/estado', [DashboardController::class, 'actualizarEstadoCita'])
            ->name('citas.estado.actualizar');
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

// ── Flujo del Paciente (Rutas PÚBLICAS) ──────────────────────────────────
Route::prefix('paciente')->name('paciente.')->group(function () {
    // Ver los detalles públicos de un profesional (su portafolio y servicios)
    Route::get('/profesional/{id}', [\App\Http\Controllers\Api\PacienteController::class, 'portafolio']);
    // Obtener las horas libres de un profesional en una fecha específica
    Route::get('/profesional/{id}/disponibilidad', [\App\Http\Controllers\Api\DisponibilidadController::class, 'index']);
    // Crear una nueva cita (reserva)
    Route::post('/reservar', [\App\Http\Controllers\Api\ReservaController::class, 'store']);
});

// ── MAGIA DE DEMO PARA EL USUARIO ──────────────────────────────────────
Route::get('/reset-demo', function () {
    try {
        // 1. Borrar y recrear todo de cero
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);

        // 2. Sembrar la categoría base
        $categoria = \App\Models\Categoria::create([
            'nombre' => 'General', 
            'slug' => 'general', 
            'activo' => true
        ]);

        // 3. Sembrar Negocio
        $negocio = \App\Models\Negocio::create([
            'nombre' => 'CitasPro Demo', 
            'slug' => 'demo', 
            'categoria_id' => $categoria->id, 
            'activo' => true
        ]);

        // 4. Sembrar Profesional Maestro
        $profesional = \App\Models\Profesional::create([
            'negocio_id'   => $negocio->id,
            'nombre'       => 'Maestro',
            'apellido'     => 'Demo',
            'email'        => 'demo@citaspro.com',
            'telefono'     => '+34600111222', // Para que entre al backdoor
            'especialidad' => 'Administrador',
            'activo'       => true
        ]);

        // 5. Sembrar Clientes
        $cliente1 = \App\Models\Cliente::create(['telefono' => '+34600000001', 'nombre' => 'Ana', 'apellido' => 'Gómez']);
        $cliente2 = \App\Models\Cliente::create(['telefono' => '+34600000002', 'nombre' => 'Carlos', 'apellido' => 'Ruiz']);
        $cliente3 = \App\Models\Cliente::create(['telefono' => '+34600000003', 'nombre' => 'Laura', 'apellido' => 'Méndez']);

        // 6. Servicios de ejemplo variados
        $servicioVIP = \App\Models\Servicio::create([
            'negocio_id' => $negocio->id, 
            'nombre' => 'Tratamiento VIP (Masaje + Spa)',
            'duracion_minutos' => 60, 
            'precio' => 45.00, 
            'activo' => true
        ]);

        $servicioCorte = \App\Models\Servicio::create([
            'negocio_id' => $negocio->id, 
            'nombre' => 'Corte de Cabello Clásico',
            'duracion_minutos' => 30, 
            'precio' => 15.00, 
            'activo' => true
        ]);

        $servicioZapatos = \App\Models\Servicio::create([
            'negocio_id' => $negocio->id, 
            'nombre' => 'Reparación de Calzado (Zapatería)',
            'duracion_minutos' => 45, 
            'precio' => 25.00, 
            'activo' => true
        ]);
        
        $servicios_ids = [$servicioVIP->id, $servicioCorte->id, $servicioZapatos->id];

        // 7. Citas Pasadas
        for ($i = 1; $i <= 7; $i++) {
            $serv_id = $servicios_ids[array_rand($servicios_ids)];
            $cita = \App\Models\Cita::create([
                'codigo_referencia' => 'DEMO-PASADA-' . $i,
                'negocio_id' => $negocio->id,
                'cliente_id' => ($i % 2 == 0) ? $cliente1->id : $cliente2->id,
                'profesional_id' => $profesional->id,
                'servicio_id' => $serv_id,
                'fecha' => now()->subDays($i)->format('Y-m-d'),
                'hora_inicio' => '10:00:00',
                'hora_fin' => '11:00:00',
                'duracion_min' => 60,
                'estado' => 'completada',
                'precio_total' => 45.00,
            ]);
            \App\Models\Pago::create([
                'cita_id' => $cita->id,
                'cliente_id' => $cita->cliente_id,
                'negocio_id' => $negocio->id,
                'monto' => 45.00,
                'monto_total' => 45.00,
                'metodo' => 'tarjeta',
                'estado' => 'completado',
            ]);
        }

        // 8. Citas HOY
        $horasHoy = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00'];
        foreach ($horasHoy as $index => $horaStr) {
            $serv_id = $servicios_ids[array_rand($servicios_ids)];
            \App\Models\Cita::create([
                'codigo_referencia' => 'DEMO-HOY-' . $index,
                'negocio_id' => $negocio->id,
                'cliente_id' => ($index % 3 == 0) ? $cliente3->id : $cliente1->id,
                'profesional_id' => $profesional->id,
                'servicio_id' => $serv_id,
                'fecha' => now()->format('Y-m-d'),
                'hora_inicio' => $horaStr . ':00',
                'hora_fin' => substr($horaStr, 0, 2) . ':50:00',
                'duracion_min' => 50,
                'estado' => ($index < 2) ? 'completada' : 'confirmada',
                'precio_total' => 45.00,
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => '¡EXITO! La base de datos ha sido borrada desde cero y las 13 citas han sido inyectadas perfectamente.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'HUBO UN ERROR: ' . $e->getMessage(),
            'linea' => $e->getLine()
        ], 500);
    }
});

// ─── Rutas PROTEGIDAS (requieren token Sanctum) ─────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Reseñas ───────────────────────────────────────────────────────────
    Route::prefix('resenas')->name('resenas.')->group(function () {
        Route::get('/negocio/{negocio}', [ResenaController::class, 'porNegocio']);
        Route::get('/profesional/{profesional}', [ResenaController::class, 'porProfesional']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [ResenaController::class, 'store']);
        });
    });



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
