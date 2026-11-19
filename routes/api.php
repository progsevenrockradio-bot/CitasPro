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
use App\Http\Controllers\Api\SuperAdmin\WebConfigController;
use App\Http\Controllers\Api\InvoiceController;
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

// ── Categorías Públicas
Route::get('/categorias', function () {
    return response()->json(\App\Models\Categoria::where('activo', true)->get());
});

// ── Países y Prefijos Telefónicos Públicos
Route::get('/paises', function () {
    return response()->json(
        \App\Models\Pais::where('activo', true)
            ->orderBy('orden_preferencia', 'asc')
            ->orderBy('nombre', 'asc')
            ->get()
    );
});

Route::get('/paises/{pais}/fiscal-fields', function (\App\Models\Pais $pais) {
    return response()->json([
        'success' => true,
        'fiscal_fields' => $pais->fiscal_fields,
    ]);
});

// ── Autenticación de Profesionales (Contraseña & 2FA)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login-contrasena', [OtpAuthController::class, 'loginContrasena'])->name('login_contrasena');
    Route::post('/otp/enviar', [OtpAuthController::class, 'enviar'])->name('otp.enviar');
    Route::post('/otp/verificar', [OtpAuthController::class, 'verificar'])->name('otp.verificar');
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

// ── Ubicaciones Jerárquicas ───────────────────────────────────────────────────
Route::get('/locations/states/{pais_id}', [\App\Http\Controllers\Public\LocationController::class, 'states'])->name('locations.states');
Route::get('/locations/cities/{estado_id}', [\App\Http\Controllers\Public\LocationController::class, 'cities'])->name('locations.cities');

// ── Registro de Consentimiento Legal ──────────────────────────────────────────
Route::post('/consent/log', [\App\Http\Controllers\Api\ConsentController::class, 'log'])->name('consent.log');

// ── Directorio Público de Negocios ────────────────────────────────────────────
Route::get('/directorio', [\App\Http\Controllers\Public\DirectorioController::class, 'index'])->name('public.directorio');
Route::get('/directorio/sugerencias', [\App\Http\Controllers\Public\DirectorioController::class, 'sugerencias'])->name('public.directorio.sugerencias');
Route::post('/directorio/track-view/{id}', [\App\Http\Controllers\Public\DirectorioController::class, 'trackView'])->name('public.directorio.track_view');
Route::get('/directorio/{negocio:slug}', [\App\Http\Controllers\Public\DirectorioController::class, 'show'])->name('public.directorio.show');

// ── Reserva Pública por Slug del Negocio ──────────────────────────────────────
// El profesional comparte: citaspro.app/{slug}/book
Route::prefix('public/{negocio:slug}')->name('public.')->group(function () {
    // Info del negocio, servicios y profesionales para montar la página
    Route::get('/', [\App\Http\Controllers\Public\ReservaPublicaController::class, 'show'])->name('show');
    // Slots disponibles: ?fecha=YYYY-MM-DD&profesional_id=1
    Route::get('/disponibilidad', [\App\Http\Controllers\Public\ReservaPublicaController::class, 'disponibilidad'])->name('disponibilidad');
    // Verificar si el cliente requiere historia clínica
    Route::get('/check-cliente', [\App\Http\Controllers\Public\ReservaPublicaController::class, 'checkCliente'])->name('check_cliente');
    // Crear la reserva pública (rate limit: 5 req/min por IP)
    Route::post('/reservar', [\App\Http\Controllers\Public\ReservaPublicaController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reservar');
    Route::post('/confirmar-pago', [\App\Http\Controllers\Public\ReservaPublicaController::class, 'confirmarPago'])->name('confirmar-pago');
});

// ── Demo (Reset Database) — SOLO entorno local ─────────────────

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS (requieren: Authorization: Bearer {token})
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Sesión y perfil (Fuera del middleware para poder desloguear o ver perfil si el plan venció)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [OtpAuthController::class, 'me'])->name('me');
        Route::patch('/perfil', [OtpAuthController::class, 'updatePerfil'])->name('perfil.update');
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
            
            // Exportación y envío de listados
            Route::get('/citas/export-pdf', [\App\Http\Controllers\Api\ExportController::class, 'exportarCitasPdf'])->name('citas.export.pdf');
            Route::post('/citas/send-email', [\App\Http\Controllers\Api\ExportController::class, 'enviarCitasEmail'])->name('citas.send.email');
        });

        // ── Facturación (VeriFactu)
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
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

        // ── Gestión de Reseñas / Testimonios (Dashboard)
        Route::prefix('dashboard/resenas')->name('dashboard.resenas.')->group(function () {
            Route::get('/', [ResenaController::class, 'dashboardIndex'])->name('index');
            Route::patch('/{id}/toggle-activo', [ResenaController::class, 'toggleActivo'])->name('toggle-activo');
        });

        // ── Reseñas (Creación)
        Route::post('/resenas', [ResenaController::class, 'store'])->name('resenas.store');

        // ── Pagos
        Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
        Route::get('/pagos/{pago}/factura-pdf', [PagoController::class, 'descargarFacturaPdf'])->name('pagos.factura.pdf');
        Route::post('/pagos/procesar', [PagoController::class, 'procesar'])->name('pagos.procesar');

        // ── Gestión de Citas del Profesional (General)
        Route::prefix('citas')->name('citas.')->group(function () {
            Route::get('/', [CitaController::class, 'index'])->name('index');
            Route::post('/', [CitaController::class, 'store'])->name('store');
            Route::patch('/{id}', [CitaController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [CitaController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Gestión de Citas Pro (General segmentado)
        Route::prefix('citas/pro')->name('citas.pro.')->middleware('can:view-pro-appointments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ProCitaController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\ProCitaController::class, 'store'])->name('store');
            Route::patch('/{id}', [\App\Http\Controllers\Api\ProCitaController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Api\ProCitaController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Gestión de Citas Médicas
        Route::prefix('citas/medical')->name('citas.medical.')->middleware('can:view-medical-appointments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\MedicalCitaController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\MedicalCitaController::class, 'store'])->name('store');
            Route::patch('/{id}', [\App\Http\Controllers\Api\MedicalCitaController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Api\MedicalCitaController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Gestión de Citas Dentales
        Route::prefix('citas/dental')->name('citas.dental.')->middleware('can:view-dental-appointments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\DentalCitaController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\DentalCitaController::class, 'store'])->name('store');
            Route::patch('/{id}', [\App\Http\Controllers\Api\DentalCitaController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Api\DentalCitaController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        // ── Directorio de Clientes del Negocio
        Route::prefix('clientes')->name('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index');
            Route::get('/{id}', [ClienteController::class, 'show'])->name('show')->where('id', '[0-9]+');
            Route::patch('/{id}', [ClienteController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::post('/{id}/ficha', [FichaClinicaController::class, 'store'])->name('ficha.store')->where('id', '[0-9]+');
        });

        Route::prefix('negocio')->name('negocio.')->group(function () {
            Route::get('/', [NegocioController::class, 'me'])->name('show');
            Route::patch('/', [NegocioController::class, 'update'])->name('update');
            Route::post('/', [NegocioController::class, 'update'])->name('update_post');
            Route::delete('/', [NegocioController::class, 'destroy'])->name('destroy');
            Route::post('/whatsapp/conectar', [WhatsAppQrController::class, 'conectar'])->name('whatsapp.conectar');
            Route::post('/datos-fiscales', [NegocioController::class, 'updateFiscalData'])->name('update_fiscal_data');
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
            Route::delete('/google/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.disconnect');
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

// ── WebConfig Público
Route::get('/web-config', [WebConfigController::class, 'index']);

// ── WebConfig Admin
Route::middleware(['auth:sanctum', 'is_superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::post('/web-config', [WebConfigController::class, 'update']);
});
