<?php

namespace App\Console\Commands;

use App\Mail\CitaRecordatorioMail;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * RecordarCitasCommand
 *
 * Comando manual alternativo para enviar un único recordatorio por correo
 * electrónico por cita (ventana principal 24h y respaldo 1h [0..90m]).
 * Comparte el bloqueo por fila (lockForUpdate) sobre recordatorio_enviado
 * para no duplicar envíos si coincide con citas:recordatorios.
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
    protected $description = 'Envía un único recordatorio por correo electrónico por cita (ventana 24h y respaldo 1h).';

    private int $totalProcesadas  = 0;
    private int $totalEnviadas    = 0;
    private int $totalFallidas    = 0;
    private int $totalOmitidas    = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $soloHoy    = (bool) $this->option('solo-hoy');
        $citaId     = $this->option('cita');
        $inicio     = now();

        $this->info("🔔 CitasPro — Motor de Recordatorios por Correo");
        $this->info("   Iniciado: " . $inicio->format('Y-m-d H:i:s'));

        if ($dryRun) {
            $this->warn("   ⚠️  Modo DRY-RUN: No se enviarán correos reales.");
        }

        $this->newLine();

        $citas = $this->obtenerCitas(citaId: $citaId, soloHoy: $soloHoy);

        if ($citas->isEmpty()) {
            $this->info("✅ No hay citas pendientes de recordatorio en este momento.");
            Log::info('RecordarCitasCommand: Sin citas para recordar.', ['hora' => $inicio->toIso8601String()]);
            return self::SUCCESS;
        }

        $this->info("📋 Citas encontradas para recordar: <comment>{$citas->count()}</comment>");
        $this->newLine();

        foreach ($citas as $cita) {
            $this->procesarCita($cita, $dryRun);
        }

        $this->newLine();
        $this->generarReporte($inicio);

        if ($this->totalFallidas > 0 && !$dryRun) {
            $this->registrarFallosEnLog();
        }

        return self::SUCCESS;
    }

    /**
     * Consulta la BD buscando citas elegibles aún no recordadas dentro de la
     * ventana principal (24h ± 1h) o la ventana de respaldo (0..90 min).
     */
    private function obtenerCitas(?string $citaId, bool $soloHoy): \Illuminate\Database\Eloquent\Collection
    {
        $horasAntes   = (int) config('services.notificaciones.recordatorio_horas_antes', 24);
        $minutosUrg   = (int) config('services.notificaciones.recordatorio_minutos_urgente', 60);

        $query = Cita::query()
            ->with(['cliente', 'servicio', 'negocio', 'profesional'])
            ->where('recordatorio_enviado', false)
            ->whereIn('estado', ['confirmada', 'pendiente'])
            ->whereDate('fecha', '>=', now()->subDay()->toDateString())
            ->whereDate('fecha', '<=', now()->addDays(3)->toDateString());

        if ($citaId) {
            $query->where('id', $citaId);
        }

        $filtradas = $query->orderBy('fecha')->orderBy('hora_inicio')->get()
            ->filter(function (Cita $cita) use ($horasAntes, $minutosUrg, $soloHoy) {
                if (!$cita->negocio || !$cita->cliente || !$cita->servicio || !$cita->profesional) {
                    return false;
                }
                $tz = $cita->negocio->getTimezoneEfectiva();
                $ahoraNegocio = now($tz);
                $fechaStr = $cita->fecha instanceof Carbon ? $cita->fecha->format('Y-m-d') : (string) $cita->fecha;

                if ($soloHoy && $fechaStr !== $ahoraNegocio->toDateString()) {
                    return false;
                }

                $horaInicioCita = Carbon::parse("{$fechaStr} {$cita->hora_inicio}", $tz);
                $v24Ini = $ahoraNegocio->copy()->addHours($horasAntes - 1);
                $v24Fin = $ahoraNegocio->copy()->addHours($horasAntes + 1);
                $v1Ini  = $ahoraNegocio->copy();
                $v1Fin  = $ahoraNegocio->copy()->addMinutes($minutosUrg + 30);

                return $horaInicioCita->between($v24Ini, $v24Fin) || $horaInicioCita->between($v1Ini, $v1Fin);
            })
            ->values();

        return new \Illuminate\Database\Eloquent\Collection($filtradas->all());
    }

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

        if ($dryRun) {
            $this->info("     [DRY-RUN] Omitido.");
            $this->totalOmitidas++;
            return;
        }

        if (empty($cliente->email) || !filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) {
            $this->warn("     ⚠️  Sin correo electrónico válido. Omitido.");
            Log::warning("RecordarCitasCommand: Cita #{$cita->id} sin correo electrónico válido de cliente.");
            $this->totalOmitidas++;
            return;
        }

        try {
            $enviado = DB::transaction(function () use ($cita, $cliente) {
                $citaBloqueada = Cita::whereKey($cita->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$citaBloqueada
                    || $citaBloqueada->recordatorio_enviado
                    || !in_array($citaBloqueada->estado, ['confirmada', 'pendiente'], true)
                ) {
                    return false;
                }

                Mail::to($cliente->email)->send(new CitaRecordatorioMail($citaBloqueada));

                $citaBloqueada->update([
                    'recordatorio_enviado'    => true,
                    'recordatorio_enviado_en' => now(),
                ]);

                return true;
            });

            if ($enviado) {
                $this->line("     <fg=green>✓ Recordatorio por correo enviado correctamente.</>");
                $this->totalEnviadas++;
            } else {
                $this->line("     [OMITIDO] Ya recordada o cancelada concurrentemente.");
                $this->totalOmitidas++;
            }
        } catch (\Throwable $e) {
            $this->error("     ✗ Falló envío por correo. No se marca como enviado.");
            $this->totalFallidas++;

            Log::error("RecordarCitasCommand: Falló recordatorio por correo para cita #{$cita->id}", [
                'cliente_id' => $cliente->id,
                'email'      => $cliente->email,
                'error'      => $e->getMessage(),
            ]);
        }
    }

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

    private function registrarFallosEnLog(): void
    {
        Log::warning("RecordarCitasCommand finalizó con {$this->totalFallidas} fallo(s) en envío de correo.");
    }
}
