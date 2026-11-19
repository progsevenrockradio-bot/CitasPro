<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Services\WhatsAppService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosCitas extends Command
{
    protected $signature   = 'citas:recordatorios';
    protected $description = 'Envía recordatorios automáticos a clientes con citas próximas (24h y 1h antes).';

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly SmsService      $sms,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('[' . now()->format('Y-m-d H:i') . '] Iniciando envío de recordatorios...');

        $enviados24h = $this->procesarRecordatorios(
            horasMin: 23,
            horasMax: 25,
            etiqueta: '24h'
        );

        $enviados1h = $this->procesarRecordatorios(
            horasMin: 0,
            horasMax: 90, // 90 minutos = 1.5h → ventana segura
            etiqueta: '1h',
            minutosMax: 90
        );

        $total = $enviados24h + $enviados1h;
        $this->info("✅ Recordatorios enviados: {$total} (24h: {$enviados24h}, urgentes: {$enviados1h})");

        return 0;
    }

    /**
     * Busca citas en el rango de horas dado y envía recordatorios.
     */
    private function procesarRecordatorios(
        int $horasMin,
        int $horasMax,
        string $etiqueta,
        ?int $minutosMax = null
    ): int {
        // Buscar citas confirmadas/pendientes sin recordatorio evaluando en la zona horaria del negocio
        $citas = Cita::with(['cliente', 'servicio', 'negocio', 'profesional'])
            ->whereIn('estado', ['confirmada', 'pendiente'])
            ->where('recordatorio_enviado', false)
            ->whereDate('fecha', '>=', now()->subDay()->toDateString())
            ->get()
            ->filter(function (Cita $cita) use ($horasMin, $horasMax, $minutosMax) {
                if (!$cita->negocio || !$cita->cliente || !$cita->servicio || !$cita->profesional) {
                    return false;
                }
                $tz = $cita->negocio->getTimezoneEfectiva();
                $ahoraNegocio = now($tz);
                $desde = $ahoraNegocio->copy()->addHours($horasMin);
                $hasta = $minutosMax
                    ? $ahoraNegocio->copy()->addMinutes($minutosMax)
                    : $ahoraNegocio->copy()->addHours($horasMax);

                $fechaStr = $cita->fecha instanceof \Carbon\Carbon
                    ? $cita->fecha->format('Y-m-d')
                    : $cita->fecha;

                $horaInicioCita = \Carbon\Carbon::parse("{$fechaStr} {$cita->hora_inicio}", $tz);

                return $horaInicioCita->between($desde, $hasta);
            });

        if ($citas->isEmpty()) {
            $this->line("  → Sin citas próximas para recordatorio {$etiqueta}.");
            return 0;
        }

        $enviados = 0;

        foreach ($citas as $cita) {
            try {
                if (empty($cita->cliente->telefono)) {
                    Log::warning("EnviarRecordatoriosCitas: Cita #{$cita->id} sin teléfono; no se envía ni se marca.");
                    continue;
                }

                $this->line("  → Recordatorio {$etiqueta}: Cita #{$cita->id} — {$cita->cliente->nombre} ({$cita->cliente->telefono})");

                // Intentar WhatsApp primero, SMS como respaldo
                $enviado = $this->whatsapp->enviarRecordatorio($cita);

                if (!$enviado) {
                    Log::warning("Recordatorio {$etiqueta}: WhatsApp falló para cita #{$cita->id}, usando SMS.");
                    $enviado = $this->sms->enviarRecordatorio($cita);
                }

                if (!$enviado) {
                    Log::error("EnviarRecordatoriosCitas: Falló WhatsApp y SMS para cita #{$cita->id}; no se marca como enviado.");
                    $this->error("  ✗ Falló el envío en cita #{$cita->id}; no se marca como enviado.");
                    continue;
                }

                // Marcar como enviado solo cuando algún canal aceptó el envío
                $cita->update([
                    'recordatorio_enviado'    => true,
                    'recordatorio_enviado_en' => now(),
                ]);

                $enviados++;

            } catch (\Exception $e) {
                Log::error("EnviarRecordatoriosCitas: Error en cita #{$cita->id} — " . $e->getMessage());
                $this->error("  ✗ Error en cita #{$cita->id}: " . $e->getMessage());
            }
        }

        return $enviados;
    }
}
