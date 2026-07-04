<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Services\WhatsAppService;
use App\Services\SmsService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * RecordarCitasCommand
 *
 * Artisan command que busca citas próximas y envía recordatorios a los clientes.
 * Se ejecuta automáticamente cada hora via el Scheduler de Laravel.
 *
 * Lógica de selección:
 *   - Citas con estado 'confirmada' o 'pendiente'
 *   - Que sean dentro de exactamente 24h (configurable)  
 *   - O que sean dentro de 60 minutos (recordatorio urgente)
 *   - Que aún no hayan recibido recordatorio
 *
 * Estrategia de envío:
 *   1. WhatsApp Cloud API (preferido, costo cero con plantillas)
 *   2. Twilio SMS (respaldo de pago)
 *   3. Email-to-SMS (respaldo gratuito, operador)
 *
 * Uso manual:
 *   php artisan citas:recordar
 *   php artisan citas:recordar --dry-run      # Solo muestra sin enviar
 *   php artisan citas:recordar --solo-hoy     # Solo citas de hoy
 *   php artisan citas:recordar --cita=123     # Solo una cita específica
 */
class RecordarCitasCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'citas:recordar
                            {--dry-run    : Simula el proceso sin enviar mensajes}
                            {--solo-hoy  : Procesa únicamente citas del día de hoy}
                            {--cita=     : Procesa solo la cita con este ID}
                            {--verbose-log : Log detallado en consola}';

    /**
     * @var string
     */
    protected $description = 'Envía recordatorios de citas próximas via WhatsApp (con fallback a SMS).';

    // Contadores para el reporte final
    private int $totalProcesadas  = 0;
    private int $totalEnviadas    = 0;
    private int $totalFallidas    = 0;
    private int $totalOmitidas    = 0;

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly SmsService      $sms,
        private readonly TelegramService $telegram,
    ) {
        parent::__construct();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $soloHoy    = (bool) $this->option('solo-hoy');
        $citaId     = $this->option('cita');
        $inicio     = now();

        $this->info("🔔 CitasPro — Motor de Recordatorios");
        $this->info("   Iniciado: " . $inicio->format('Y-m-d H:i:s'));

        if ($dryRun) {
            $this->warn("   ⚠️  Modo DRY-RUN: No se enviarán mensajes reales.");
        }

        $this->newLine();

        // ── Obtener citas a procesar ─────────────────────────────────────────
        $citas = $this->obtenerCitas(citaId: $citaId, soloHoy: $soloHoy);

        if ($citas->isEmpty()) {
            $this->info("✅ No hay citas pendientes de recordatorio en este momento.");
            Log::info('RecordarCitasCommand: Sin citas para recordar.', ['hora' => $inicio->toIso8601String()]);
            return self::SUCCESS;
        }

        $this->info("📋 Citas encontradas para recordar: <comment>{$citas->count()}</comment>");
        $this->newLine();

        // ── Procesar cada cita ───────────────────────────────────────────────
        foreach ($citas as $cita) {
            $this->procesarCita($cita, $dryRun);
        }

        // ── Reporte final ────────────────────────────────────────────────────
        $this->newLine();
        $this->generarReporte($inicio);

        // ── Notificar al dueño del negocio si hubo muchos fallos ─────────────
        if ($this->totalFallidas > 0 && !$dryRun) {
            $this->alertarFallos();
        }

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obtener citas que necesitan recordatorio
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Consulta la BD buscando citas que:
     *  a) Están dentro de las próximas 24h (recordatorio principal)
     *  b) Están dentro de la próxima 1h (recordatorio urgente)
     *  c) No han recibido recordatorio aún
     *  d) Tienen estado 'confirmada' o 'pendiente'
     */
    private function obtenerCitas(?string $citaId, bool $soloHoy): \Illuminate\Database\Eloquent\Collection
    {
        $horasAntes   = (int) config('services.notificaciones.recordatorio_horas_antes', 24);
        $minutosUrg   = (int) config('services.notificaciones.recordatorio_minutos_urgente', 60);

        // Ventanas de tiempo
        $ventana24h_inicio = now()->addHours($horasAntes - 1);       // 23h desde ahora
        $ventana24h_fin    = now()->addHours($horasAntes + 1);       // 25h desde ahora
        $ventana1h_inicio  = now()->addMinutes($minutosUrg - 30);    // 30min desde ahora
        $ventana1h_fin     = now()->addMinutes($minutosUrg + 30);    // 90min desde ahora

        $query = Cita::query()
            ->with(['cliente', 'servicio', 'negocio', 'profesional'])
            ->where('recordatorio_enviado', false)
            ->whereIn('estado', ['confirmada', 'pendiente'])
            ->where(function ($q) use ($ventana24h_inicio, $ventana24h_fin, $ventana1h_inicio, $ventana1h_fin) {
                // Ventana de 24h (recordatorio principal)
                $q->where(function ($sub) use ($ventana24h_inicio, $ventana24h_fin) {
                    $sub->whereDate('fecha', '>=', $ventana24h_inicio->toDateString())
                        ->whereDate('fecha', '<=', $ventana24h_fin->toDateString())
                        ->whereRaw(
                            "STR_TO_DATE(CONCAT(fecha, ' ', hora_inicio), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?",
                            [$ventana24h_inicio, $ventana24h_fin]
                        );
                })
                // O ventana de 1h (recordatorio urgente)
                ->orWhere(function ($sub) use ($ventana1h_inicio, $ventana1h_fin) {
                    $sub->whereDate('fecha', '>=', $ventana1h_inicio->toDateString())
                        ->whereDate('fecha', '<=', $ventana1h_fin->toDateString())
                        ->whereRaw(
                            "STR_TO_DATE(CONCAT(fecha, ' ', hora_inicio), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?",
                            [$ventana1h_inicio, $ventana1h_fin]
                        );
                });
            });

        // Filtros opcionales
        if ($citaId) {
            $query->where('id', $citaId);
        }

        if ($soloHoy) {
            $query->whereDate('fecha', today());
        }

        return $query->orderBy('fecha')->orderBy('hora_inicio')->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Procesar una cita individual
    // ─────────────────────────────────────────────────────────────────────────

    private function procesarCita(Cita $cita, bool $dryRun): void
    {
        $this->totalProcesadas++;

        $cliente  = $cita->cliente;
        $servicio = $cita->servicio;
        $negocio  = $cita->negocio;
        $fecha    = Carbon::parse($cita->fecha)->format('d/m/Y');
        $hora     = substr($cita->hora_inicio, 0, 5);

        $this->line(
            "  → Cita <fg=cyan>#{$cita->id}</> | {$cliente->nombre_completo} | "
            . "{$servicio->nombre} | {$fecha} {$hora} | {$negocio->nombre}"
        );

        // Si es dry-run, no enviamos nada
        if ($dryRun) {
            $this->info("     [DRY-RUN] Omitido.");
            $this->totalOmitidas++;
            return;
        }

        // ── Verificar que el cliente tiene teléfono ──────────────────────────
        if (empty($cliente->telefono)) {
            $this->warn("     ⚠️  Sin teléfono. Omitido.");
            Log::warning("RecordarCitasCommand: Cita #{$cita->id} sin teléfono de cliente.");
            $this->totalOmitidas++;
            return;
        }

        // ── Intento 1: WhatsApp Cloud API ────────────────────────────────────
        $enviado = $this->whatsapp->enviarRecordatorio($cita);

        if ($enviado) {
            $this->line("     <fg=green>✓ WhatsApp enviado correctamente.</>");
            $this->totalEnviadas++;
        } else {
            // ── Intento 2: SMS (Twilio o Email-to-SMS) ────────────────────────
            $this->warn("     WhatsApp falló. Intentando SMS de respaldo...");
            $enviado = $this->sms->enviarRecordatorio($cita);

            if ($enviado) {
                $this->line("     <fg=yellow>~ SMS enviado (respaldo).</>");
                $this->totalEnviadas++;
            } else {
                $this->error("     ✗ Falló WhatsApp Y SMS. Sin recordatorio enviado.");
                $this->totalFallidas++;

                Log::error("RecordarCitasCommand: Falló recordatorio para cita #{$cita->id}", [
                    'cliente_id' => $cliente->id,
                    'telefono'   => $cliente->telefono,
                ]);

                return; // No marcar como enviado si falló todo
            }
        }

        // ── Marcar cita como recordada ───────────────────────────────────────
        $cita->update([
            'recordatorio_enviado'    => true,
            'recordatorio_enviado_en' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte y alertas
    // ─────────────────────────────────────────────────────────────────────────

    private function generarReporte(Carbon $inicio): void
    {
        $duracion = $inicio->diffInSeconds(now());

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total procesadas',  $this->totalProcesadas],
                ['Enviadas OK',       $this->totalEnviadas],
                ['Omitidas',          $this->totalOmitidas],
                ['Fallidas',          $this->totalFallidas],
                ['Duración (seg)',    $duracion],
            ]
        );

        Log::info('RecordarCitasCommand: Ejecución completada.', [
            'procesadas' => $this->totalProcesadas,
            'enviadas'   => $this->totalEnviadas,
            'omitidas'   => $this->totalOmitidas,
            'fallidas'   => $this->totalFallidas,
            'duracion_s' => $duracion,
        ]);
    }

    /**
     * Si hay fallos, notifica al bot de Telegram del sistema.
     * Requiere configurar TELEGRAM_ADMIN_CHAT_ID en .env.
     */
    private function alertarFallos(): void
    {
        $adminChatId = config('services.telegram.admin_chat_id');

        if (empty($adminChatId)) {
            return;
        }

        $mensaje = "⚠️ <b>CitasPro Alert</b>\n\n"
                 . "El comando <code>citas:recordar</code> tuvo {$this->totalFallidas} fallo(s) al enviar recordatorios.\n"
                 . "Revisa los logs: <code>storage/logs/laravel.log</code>";

        $this->telegram->enviarTextoLibre($adminChatId, $mensaje);
    }
}
