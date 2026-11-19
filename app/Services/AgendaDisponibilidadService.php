<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Negocio;
use App\Models\Profesional;
use Carbon\Carbon;

class AgendaDisponibilidadService
{
    private const DAY_KEYS = [
        1 => ['lun', 'lunes'],
        2 => ['mar', 'martes'],
        3 => ['mié', 'mie', 'miercoles', 'miércoles'],
        4 => ['jue', 'jueves'],
        5 => ['vie', 'viernes'],
        6 => ['sáb', 'sab', 'sabado', 'sábado'],
        7 => ['dom', 'domingo'],
    ];

    public function resolveDayWindow(Negocio $negocio, Profesional $profesional, string $fecha): array
    {
        $tz = $negocio->getTimezoneEfectiva();
        $isoDay = Carbon::parse($fecha, $tz)->dayOfWeekIso;
        $keys = self::DAY_KEYS[$isoDay] ?? [];

        $negCfgAll = is_array($negocio->horario_apertura) ? $negocio->horario_apertura : [];
        $profCfgAll = is_array($profesional->horario_disponible) ? $profesional->horario_disponible : [];

        $hasNeg = count($negCfgAll) > 0;
        $hasProf = count($profCfgAll) > 0;

        $inicio = '09:00';
        $fin = '19:00';
        $descansos = [];

        if ($hasNeg) {
            $negCfg = $this->findDayConfig($negCfgAll, $keys);
            if (!$negCfg || $this->isDayClosed($negCfg) || empty($negCfg['inicio']) || empty($negCfg['fin'])) {
                return ['open' => false, 'reason' => 'closed', 'timezone' => $tz];
            }
            $inicio = $this->normalizeHm((string) $negCfg['inicio']);
            $fin = $this->normalizeHm((string) $negCfg['fin']);
            $descansos = array_merge($descansos, $this->extractBreaks($negCfg));
        }

        if ($hasProf) {
            $profCfg = $this->findDayConfig($profCfgAll, $keys);
            if (!$profCfg || $this->isDayClosed($profCfg) || empty($profCfg['inicio']) || empty($profCfg['fin'])) {
                return ['open' => false, 'reason' => 'closed', 'timezone' => $tz];
            }
            $pInicio = $this->normalizeHm((string) $profCfg['inicio']);
            $pFin = $this->normalizeHm((string) $profCfg['fin']);
            if ($hasNeg) {
                $inicio = $pInicio > $inicio ? $pInicio : $inicio;
                $fin = $pFin < $fin ? $pFin : $fin;
            } else {
                $inicio = $pInicio;
                $fin = $pFin;
            }
            $descansos = array_merge($descansos, $this->extractBreaks($profCfg));
        }

        $workStart = Carbon::parse("{$fecha} {$inicio}", $tz);
        $workEnd = Carbon::parse("{$fecha} {$fin}", $tz);
        if ($workStart->gte($workEnd)) {
            return ['open' => false, 'reason' => 'closed', 'timezone' => $tz];
        }

        return [
            'open'      => true,
            'inicio'    => $inicio,
            'fin'       => $fin,
            'descansos' => $descansos,
            'timezone'  => $tz,
        ];
    }

    public function slotsDisponibles(
        Negocio $negocio,
        Profesional $profesional,
        string $fecha,
        ?int $duracionMin = null,
        int $capacidad = 1
    ): array {
        $tz = $negocio->getTimezoneEfectiva();
        $ahora = Carbon::now($tz);

        if ($fecha < $ahora->toDateString()) {
            return [];
        }

        $window = $this->resolveDayWindow($negocio, $profesional, $fecha);
        if (empty($window['open'])) {
            return [];
        }

        $step = max(5, (int) ($negocio->duracion_turno_min ?? 30));
        $duracion = max(5, (int) ($duracionMin ?? $step));
        $capacidad = max(1, $capacidad);

        $workStart = Carbon::parse("{$fecha} {$window['inicio']}", $tz);
        $workEnd = Carbon::parse("{$fecha} {$window['fin']}", $tz);

        $breaks = [];
        foreach ($window['descansos'] as $b) {
            $bStart = Carbon::parse("{$fecha} {$b['inicio']}", $tz);
            $bEnd = Carbon::parse("{$fecha} {$b['fin']}", $tz);
            if ($bStart->lt($bEnd)) {
                $breaks[] = [$bStart, $bEnd];
            }
        }

        $citasActivas = Cita::where('profesional_id', $profesional->id)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->get(['hora_inicio', 'hora_fin'])
            ->map(function (Cita $c) use ($fecha, $tz) {
                return [
                    Carbon::parse("{$fecha} " . substr((string) $c->hora_inicio, 0, 8), $tz),
                    Carbon::parse("{$fecha} " . substr((string) $c->hora_fin, 0, 8), $tz),
                ];
            })
            ->all();

        $slots = [];
        $esHoy = $fecha === $ahora->toDateString();
        $cursor = $workStart->copy();

        while ($cursor->copy()->addMinutes($duracion)->lte($workEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($duracion);

            if ($esHoy && $cursor->lte($ahora)) {
                $cursor->addMinutes($step);
                continue;
            }

            $enDescanso = false;
            foreach ($breaks as [$bStart, $bEnd]) {
                if ($bStart->lt($slotEnd) && $bEnd->gt($cursor)) {
                    $enDescanso = true;
                    break;
                }
            }
            if ($enDescanso) {
                $cursor->addMinutes($step);
                continue;
            }

            $solapamientos = 0;
            foreach ($citasActivas as [$cStart, $cEnd]) {
                if ($cStart->lt($slotEnd) && $cEnd->gt($cursor)) {
                    $solapamientos++;
                    if ($solapamientos >= $capacidad) {
                        break;
                    }
                }
            }

            if ($solapamientos < $capacidad) {
                $slots[] = $cursor->format('H:i');
            }

            $cursor->addMinutes($step);
        }

        return $slots;
    }

    public function validarSlotParaReserva(
        Negocio $negocio,
        Profesional $profesional,
        string $fecha,
        string $hora,
        int $duracionMin
    ): ?array {
        $tz = $negocio->getTimezoneEfectiva();
        $start = Carbon::parse("{$fecha} {$hora}", $tz);
        $end = $start->copy()->addMinutes(max(5, $duracionMin));

        if ($start->lte(Carbon::now($tz))) {
            return [
                'status'  => 422,
                'message' => 'No puedes reservar en una fecha u hora pasada.',
            ];
        }

        $window = $this->resolveDayWindow($negocio, $profesional, $fecha);
        if (empty($window['open'])) {
            return [
                'status'  => 422,
                'message' => 'El negocio o profesional no atiende en la fecha seleccionada.',
            ];
        }

        $workStart = Carbon::parse("{$fecha} {$window['inicio']}", $tz);
        $workEnd = Carbon::parse("{$fecha} {$window['fin']}", $tz);

        if ($start->lt($workStart) || $end->gt($workEnd)) {
            return [
                'status'  => 422,
                'message' => 'El horario seleccionado está fuera de la jornada laboral o supera la hora de cierre.',
            ];
        }

        foreach ($window['descansos'] as $b) {
            $bStart = Carbon::parse("{$fecha} {$b['inicio']}", $tz);
            $bEnd = Carbon::parse("{$fecha} {$b['fin']}", $tz);
            if ($bStart->lt($end) && $bEnd->gt($start)) {
                return [
                    'status'  => 422,
                    'message' => 'El horario seleccionado coincide con un horario de descanso.',
                ];
            }
        }

        return null;
    }

    public function tieneSolapamiento(
        int $profesionalId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        int $capacidad = 1
    ): bool {
        $count = Cita::where('profesional_id', $profesionalId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->count();

        return $count >= max(1, $capacidad);
    }

    private function findDayConfig(array $all, array $keys): ?array
    {
        $normalized = [];
        foreach ($all as $k => $v) {
            if (is_string($k) && is_array($v)) {
                $normalized[mb_strtolower($k, 'UTF-8')] = $v;
            }
        }
        foreach ($keys as $k) {
            $lk = mb_strtolower($k, 'UTF-8');
            if (isset($normalized[$lk])) {
                return $normalized[$lk];
            }
        }
        return null;
    }

    private function isDayClosed(array $cfg): bool
    {
        if (!empty($cfg['cerrado'])) {
            return true;
        }
        if (array_key_exists('activo', $cfg) && !$cfg['activo']) {
            return true;
        }
        return false;
    }

    private function extractBreaks(array $cfg): array
    {
        $out = [];
        if (!empty($cfg['descansos']) && is_array($cfg['descansos'])) {
            foreach ($cfg['descansos'] as $b) {
                if (!is_array($b) || empty($b['inicio']) || empty($b['fin'])) {
                    continue;
                }
                $bi = $this->normalizeHm((string) $b['inicio']);
                $bf = $this->normalizeHm((string) $b['fin']);
                if ($bi < $bf) {
                    $out[] = ['inicio' => $bi, 'fin' => $bf];
                }
            }
        }
        if (!empty($cfg['descanso_inicio']) && !empty($cfg['descanso_fin'])) {
            $bi = $this->normalizeHm((string) $cfg['descanso_inicio']);
            $bf = $this->normalizeHm((string) $cfg['descanso_fin']);
            if ($bi < $bf) {
                $out[] = ['inicio' => $bi, 'fin' => $bf];
            }
        }
        return $out;
    }

    private function normalizeHm(string $hm): string
    {
        return substr(trim($hm), 0, 5);
    }
}
