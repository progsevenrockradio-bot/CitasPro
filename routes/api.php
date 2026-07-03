<?php

use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PortafolioController;
use App\Http\Controllers\Api\TelegramBotController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\ReservaController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\ResenaController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\NegocioController as AdminNegocioController;
use App\Http\Controllers\Api\Admin\ProfesionalController as AdminProfesionalController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\NegocioController;
use App\Http\Controllers\Api\SuscripcionController;
use App\Http\Controllers\Api\FichaClinicaController;
use App\Http\Controllers\Api\FormularioIngresoController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\WhatsAppQrController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes — CitasPro v1
|--------------------------------------------------------------------------
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

// ── Autenticación OTP
Route::prefix('auth/otp')->name('auth.otp.')->group(function () {
    Route::post('/enviar', [OtpAuthController::class, 'enviar'])->name('enviar');
    Route::post('/verificar', [OtpAuthController::class, 'verificar'])->name('verificar');
});

// ── Portafolio público (galería de trabajos del profesional)
Route::prefix('portafolio')->name('portafolio.')->group(function () {
    Route::get('/{profesionalId}', [PortafolioController::class, 'index'])
        ->name('index')
        ->where('profesionalId', '[0-9]+');
});

// ── Flujo del Paciente
Route::prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/profesional/{id}', [PacienteController::class, 'portafolio']);
    Route::get('/profesional/{id}/disponibilidad', [DisponibilidadController::class, 'index']);
    Route::post('/reservar', [ReservaController::class, 'store']);
    Route::post('/pagos/procesar', [PagoController::class, 'procesarPagoPaciente'])->name('pagos.procesar');
    Route::post('/pagos/confirmar-simulado', [PagoController::class, 'confirmarPagoPacienteSimulado'])->name('pagos.confirmar_simulado');
});

// ── Reseñas Públicas
Route::prefix('resenas')->name('resenas.')->group(function () {
    Route::get('/negocio/{negocio}', [ResenaController::class, 'porNegocio']);
    Route::get('/profesional/{profesional}', [ResenaController::class, 'porProfesional']);
});

// ── Webhook de Telegram
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle'])->name('telegram.webhook');

// ── Webhook de Stripe (Pagos de citas)
Route::post('/pagos/webhook/stripe', [PagoController::class, 'webhookStripe'])->name('pagos.webhook.stripe');

// ── Webhook de MercadoPago
Route::post('/pagos/webhook/mercadopago', [PagoController::class, 'webhookMercadoPago'])->name('pagos.webhook.mercadopago');

// ── Webhook de Redsys
Route::post('/pagos/webhook/redsys', [PagoController::class, 'webhookRedsys'])->name('pagos.webhook.redsys');

// ── Webhook de Estado de WhatsApp QR
Route::post('/whatsapp/webhook/estado/{negocioId}', [WhatsAppQrController::class, 'webhookEstado'])->name('whatsapp.webhook.estado');

// ── Planes de Suscripción (público)
Route::get('/suscripciones/planes', [SuscripcionController::class, 'planes'])->name('suscripciones.planes');

// ── Callback de Google Calendar (Público)
Route::get('/google/callback', [GoogleCalendarController::class, 'callback'])->name('google.callback');

// ── Webhook de Stripe (Suscripciones SaaS)
Route::post('/suscripciones/webhook', [SuscripcionController::class, 'webhookSuscripcion'])->name('suscripciones.webhook');

// ── Demo (Reset Database)
Route::get('/reset-demo', function () {
    try {
        Artisan::call('migrate:fresh', ['--force' => true]);

        $categoria = \App\Models\Categoria::create(['nombre' => 'General', 'slug' => 'general', 'activo' => true]);
        $negocio = \App\Models\Negocio::create(['nombre' => 'CitasPro Demo', 'slug' => 'demo', 'categoria_id' => $categoria->id, 'activo' => true]);
        
        $profesional = \App\Models\Profesional::create([
            'negocio_id'   => $negocio->id,
            'nombre'       => 'Maestro',
            'apellido'     => 'Demo',
            'email'        => 'demo@citaspro.com',
            'telefono'     => '+34600111222', 
            'especialidad' => 'Administrador',
            'activo'       => true
        ]);

        $cliente1 = \App\Models\Cliente::create(['telefono' => '+34600000001', 'nombre' => 'Ana', 'apellido' => 'Gómez']);
        $cliente2 = \App\Models\Cliente::create(['telefono' => '+34600000002', 'nombre' => 'Carlos', 'apellido' => 'Ruiz']);
        $cliente3 = \App\Models\Cliente::create(['telefono' => '+34600000003', 'nombre' => 'Laura', 'apellido' => 'Méndez']);

        $servicioVIP = \App\Models\Servicio::create(['negocio_id' => $negocio->id, 'nombre' => 'Tratamiento VIP (Masaje + Spa)', 'duracion_minutos' => 60, 'precio' => 45.00, 'activo' => true]);
        $servicioCorte = \App\Models\Servicio::create(['negocio_id' => $negocio->id, 'nombre' => 'Corte de Cabello Clásico', 'duracion_minutos' => 30, 'precio' => 15.00, 'activo' => true]);
        $servicioZapatos = \App\Models\Servicio::create(['negocio_id' => $negocio->id, 'nombre' => 'Reparación de Calzado (Zapatería)', 'duracion_minutos' => 45, 'precio' => 25.00, 'activo' => true]);
        
        $servicios_ids = [$servicioVIP->id, $servicioCorte->id, $servicioZapatos->id];

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

        return response()->json(['success' => true, 'message' => '¡EXITO! Base de datos reiniciada e inyectada perfectamente.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'HUBO UN ERROR: ' . $e->getMessage(), 'linea' => $e->getLine()], 500);
    }
});

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS (requieren: Authorization: Bearer {token})
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Sesión y perfil (Fuera del middleware para poder desloguear o ver perfil si el plan venció)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [OtpAuthController::class, 'me'])->name('me');
        Route::post('/logout', [OtpAuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [OtpAuthController::class, 'logoutTodos'])->name('logout.all');
    });

    // ── Suscripciones (Fuera del middleware para poder cambiar de plan o cancelar)
    Route::prefix('suscripciones')->name('suscripciones.')->group(function () {
        Route::post('/suscribir', [SuscripcionController::class, 'suscribir'])->name('suscribir');
        Route::delete('/cancelar', [SuscripcionController::class, 'cancelar'])->name('cancelar');
    });

    // ── Rutas de Gestión del Profesional y Citas (Protegidas contra planes inactivos/vencidos)
    Route::middleware('suscripcion.activa:free')->group(function () {

        // ── Dashboard y métricas
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/metricas', [DashboardController::class, 'metricas'])->name('metricas');
            Route::get('/metricas/{periodo}', [DashboardController::class, 'metricas'])
                ->name('metricas.periodo')
                ->where('periodo', 'mes_actual|mes_anterior|semana|anio');
            Route::get('/agenda', [DashboardController::class, 'agenda'])->name('agenda');
            Route::get('/resumen-rapido', [DashboardController::class, 'resumenRapido'])->name('resumen.rapido');
            Route::patch('/citas/{id}/estado', [DashboardController::class, 'actualizarEstadoCita'])->name('citas.estado.actualizar');
        });

        // ── Gestión de Portafolio
        Route::prefix('portafolio')->name('portafolio.')->group(function () {
            Route::post('/{profesionalId}/subir', [PortafolioController::class, 'subir'])
                ->name('subir')->where('profesionalId', '[0-9]+');
            Route::patch('/{id}', [PortafolioController::class, 'actualizar'])
                ->name('actualizar')->where('id', '[0-9]+');
            Route::delete('/{id}', [PortafolioController::class, 'eliminar'])
                ->name('eliminar')->where('id', '[0-9]+');
            Route::post('/reordenar', [PortafolioController::class, 'reordenar'])
                ->name('reordenar');
        });

        // ── Gestión de Servicios
        Route::prefix('servicios')->name('servicios.')->group(function () {
            Route::get('/', [ServicioController::class, 'index'])->name('index');
            Route::post('/', [ServicioController::class, 'store'])->name('store');
            Route::patch('/{id}', [ServicioController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [ServicioController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Gestión de Horarios
        Route::prefix('horarios')->name('horarios.')->group(function () {
            Route::get('/', [HorarioController::class, 'show'])->name('show');
            Route::put('/', [HorarioController::class, 'update'])->name('update');
        });

        // ── Gestión de Profesionales (Médicos/Staff)
        Route::prefix('profesionales')->name('profesionales.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ProfesionalController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\ProfesionalController::class, 'store'])->name('store');
            Route::patch('/{id}', [\App\Http\Controllers\Api\ProfesionalController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Api\ProfesionalController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Reseñas (Creación)
        Route::post('/resenas', [ResenaController::class, 'store'])->name('resenas.store');

        // ── Pagos
        Route::post('/pagos/procesar', [PagoController::class, 'procesar'])->name('pagos.procesar');

        // ── Gestión de Citas del Profesional
        Route::prefix('citas')->name('citas.')->group(function () {
            Route::get('/', [CitaController::class, 'index'])->name('index');
            Route::post('/', [CitaController::class, 'store'])->name('store');
            Route::patch('/{id}', [CitaController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [CitaController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Directorio de Clientes del Negocio
        Route::prefix('clientes')->name('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index');
            Route::get('/{id}', [ClienteController::class, 'show'])->name('show')->where('id', '[0-9]+');
            Route::patch('/{id}', [ClienteController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::post('/{id}/ficha', [\App\Http\Controllers\Api\FichaClinicaController::class, 'store'])->name('ficha.store')->where('id', '[0-9]+');
        });

        // ── Ajustes del Negocio
        Route::prefix('negocio')->name('negocio.')->group(function () {
            Route::get('/', [NegocioController::class, 'show'])->name('show');
            Route::patch('/', [NegocioController::class, 'update'])->name('update');
            Route::post('/whatsapp/conectar', [WhatsAppQrController::class, 'conectar'])->name('whatsapp.conectar');
        });

        // ── Módulo Médico (CitasPro Médico)
        Route::prefix('medico')->name('medico.')->group(function () {
            // Formulario de Ingreso / Intake
            Route::post('/formulario-ingreso', [FormularioIngresoController::class, 'storeOrUpdate'])->name('formulario_ingreso.store_or_update');
            Route::get('/paciente/{clienteId}/formulario-ingreso', [FormularioIngresoController::class, 'show'])->name('formulario_ingreso.show');

            // Fichas Clínicas
            Route::post('/fichas-clinicas', [FichaClinicaController::class, 'store'])->name('fichas_clinicas.store');
            Route::get('/fichas-clinicas/paciente/{clienteId}', [FichaClinicaController::class, 'indexPaciente'])->name('fichas_clinicas.index_paciente');
            Route::get('/fichas-clinicas/{id}', [FichaClinicaController::class, 'show'])->name('fichas_clinicas.show');
            Route::post('/fichas-clinicas/compartir', [FichaClinicaController::class, 'compartirAcceso'])->name('fichas_clinicas.compartir_acceso');

            // Google Calendar OAuth
            Route::get('/google/redirect', [GoogleCalendarController::class, 'redirect'])->name('google.redirect');
        });

    });

});

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS SÚPER ADMIN (Fase 3)
// ═══════════════════════════════════════════════════════════════════════════

Route::prefix('admin')->name('admin.')->group(function () {
    
    // Login de Súper Admin (email/password)
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login');

    // Rutas protegidas del Súper Admin
    Route::middleware(['auth:sanctum', \App\Http\Middleware\IsSuperAdmin::class])->group(function () {
        
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Gestión global de Negocios
        Route::get('/negocios', [AdminNegocioController::class, 'index'])->name('negocios.index');
        Route::get('/negocios/{negocio}', [AdminNegocioController::class, 'show'])->name('negocios.show');
        Route::patch('/negocios/{negocio}', [AdminNegocioController::class, 'update'])->name('negocios.update');

        // Gestión global de Profesionales
        Route::get('/profesionales', [AdminProfesionalController::class, 'index'])->name('profesionales.index');
        Route::get('/profesionales/{profesional}', [AdminProfesionalController::class, 'show'])->name('profesionales.show');
        Route::patch('/profesionales/{profesional}', [AdminProfesionalController::class, 'update'])->name('profesionales.update');
    });
});

