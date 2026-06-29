<?php

namespace App\Console\Commands;

use App\Models\Profesional;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ResumenDiarioCommand
 *
 * Envía a cada profesional (vía Telegram) un resumen de sus citas del día.
 * Se ejecuta automáticamente cada mañana a las 08:00 AM.
 *
 * Uso manual:
 *   php artisan citas:resumen-diario
 *   php artisan citas:resumen-diario --negocio=1   # Solo un negocio
 */
class ResumenDiarioCommand extends Command
{
    protected $signature = 'citas:resumen-diario
                            {--negocio= : ID del negocio a procesar (opcional)}';

    protected $description = 'Envía a cada profesional su resumen de citas del día por Telegram.';

    public function __construct(
        private readonly TelegramService $telegram
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $negocioId = $this->option('negocio');

        $this->info("📅 CitasPro — Resumen Diario de Citas");
        $this->info("   " . now()->format('l d \d\e F \d\e Y'));
        $this->newLine();

        // Obtener profesionales con Telegram configurado y citas hoy
        $query = Profesional::query()
            ->where('activo', true)
            ->where('notificaciones_telegram', true)
            ->whereNotNull('telegram_chat_id')
            ->with([
                'citas' => function ($q) {
                    $q->whereDate('fecha', today())
                        ->whereIn('estado', ['confirmada', 'pendiente'])
                        ->orderBy('hora_inicio')
                        ->with(['cliente', 'servicio']);
                },
            ]);

        if ($negocioId) {
            $query->where('negocio_id', $negocioId);
        }

        $profesionales = $query->get();

        $totalProfesionales = 0;
        $totalMensajes = 0;

        foreach ($profesionales as $profesional) {
            $citasHoy = $profesional->citas;

            if ($citasHoy->isEmpty()) {
                $this->line("  → {$profesional->nombre}: Sin citas hoy. Omitido.");
                continue;
            }

            $totalProfesionales++;

            $this->line("  → {$profesional->nombre}: {$citasHoy->count()} cita(s)");

            $enviado = $this->telegram->enviarResumenDiario($profesional, $citasHoy->all());

            if ($enviado) {
                $totalMensajes++;
                $this->line("     <fg=green>✓ Telegram enviado.</>");
            } else {
                $this->warn("     ⚠️ Falló el envío a {$profesional->nombre}");
            }
        }

        $this->newLine();
        $this->info("Resumen: {$totalMensajes}/{$totalProfesionales} profesionales notificados.");

        Log::info('ResumenDiarioCommand completado.', [
            'fecha'         => today()->toDateString(),
            'notificados'   => $totalMensajes,
            'total'         => $totalProfesionales,
        ]);

        return self::SUCCESS;
    }
}
