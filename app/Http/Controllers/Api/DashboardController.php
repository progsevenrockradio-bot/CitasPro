<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Profesional;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DashboardController
 *
 * Endpoints de métricas y resúmenes para el panel del Profesional.
 * Todos los endpoints requieren autenticación via Sanctum.
 *
 * Rutas:
 *   GET /api/dashboard/metricas           → Métricas del mes actual (autenticado)
 *   GET /api/dashboard/metricas/{periodo} → Métricas de un periodo específico
 *   GET /api/dashboard/agenda             → Citas de hoy y próximas
 *   GET /api/dashboard/resumen-rapido     → Widget compacto para apps móviles
 */
class DashboardController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/metricas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve las métricas completas del Profesional autenticado.
     *
     * Métricas incluidas:
     *   - Total de citas del mes actual
     *   - Citas completadas (concretadas)
     *   - Cancelaciones + no-shows
     *   - Tasa de completado (%)
     *   - Ingresos generados (pagos completados del mes)
     *   - Ingresos pendientes
     *   - Comparativa vs mes anterior
     *   - Distribución de citas por estado
     *   - Top 5 servicios más solicitados del mes
     *   - Citas por día de semana (para detectar picos)
     *
     * @param  Request $request
     * @param  string  $periodo  'mes_actual' | 'mes_anterior' | 'semana' | 'anio'
     */
    public function metricas(Request $request, string $periodo = 'mes_actual'): JsonResponse
    {
        $profesional = $this->resolverProfesional($request);

        if (!$profesional) {
            return response()->json([
                'success' => false,
                'message' => 'Profesional no encontrado o sin permisos.',
            ], 403);
        }

        // ── Definir ventana de tiempo ────────────────────────────────────────
        [$inicio, $fin, $labelPeriodo] = $this->ventanaTiempo($periodo);

        // ── Periodo anterior (para comparativa) ──────────────────────────────
        $duracion = $inicio->diffInDays($fin) + 1;
        $inicioAnterior = $inicio->copy()->subDays($duracion);
        $finAnterior    = $inicio->copy()->subDay();

        // ── Consultas base ────────────────────────────────────────────────────
        $queryCitas = Cita::where('profesional_id', $profesional->id)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

        $queryCitasAnt = Cita::where('profesional_id', $profesional->id)
            ->whereBetween('fecha', [$inicioAnterior->toDateString(), $finAnterior->toDateString()]);

        // ── Métricas del periodo actual ───────────────────────────────────────
        $totalCitas       = (clone $queryCitas)->count();
        $completadas      = (clone $queryCitas)->where('estado', 'completada')->count();
        $canceladas       = (clone $queryCitas)->where('estado', 'cancelada')->count();
        $noAsistio        = (clone $queryCitas)->where('estado', 'no_asistio')->count();
        $pendientes       = (clone $queryCitas)->whereIn('estado', ['pendiente', 'confirmada'])->count();
        $rechazadas       = (clone $queryCitas)->where('estado', 'rechazada')->count();

        // Cancelaciones totales = cancelaciones + no-shows
        $totalCancelaciones = $canceladas + $noAsistio;

        // Tasa de completado
        $tasaCompletado = $totalCitas > 0
            ? round(($completadas / $totalCitas) * 100, 1)
            : 0;

        // ── Ingresos del periodo ──────────────────────────────────────────────
        $ingresosData = $this->calcularIngresos($profesional->id, $inicio, $fin);

        // ── Métricas del periodo anterior (comparativa) ───────────────────────
        $totalCitasAnt  = (clone $queryCitasAnt)->count();
        $completadasAnt = (clone $queryCitasAnt)->where('estado', 'completada')->count();
        $ingresosAnt    = $this->calcularIngresos($profesional->id, $inicioAnterior, $finAnterior);

        // ── Top servicios del periodo ─────────────────────────────────────────
        $topServicios = (clone $queryCitas)
            ->select('servicio_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(precio_total) as ingresos'))
            ->with('servicio:id,nombre,precio,moneda')
            ->groupBy('servicio_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'servicio' => $c->servicio?->nombre ?? 'Desconocido',
                'total'    => $c->total,
                'ingresos' => (float) $c->ingresos,
            ]);

        // ── Distribución por estado ───────────────────────────────────────────
        $distribucionEstados = (clone $queryCitas)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // ── Citas por día de la semana ────────────────────────────────────────
        $citasPorDia = (clone $queryCitas)
            ->select(DB::raw('DAYOFWEEK(fecha) as dia_semana'), DB::raw('COUNT(*) as total'))
            ->groupBy('dia_semana')
            ->orderBy('dia_semana')
            ->get()
            ->mapWithKeys(fn ($r) => [
                $this->nombreDiaSemana($r->dia_semana) => $r->total,
            ]);

        // ── Citas por semana (evolución dentro del mes) ───────────────────────
        $evolucionSemanal = (clone $queryCitas)
            ->select(
                DB::raw('YEARWEEK(fecha, 1) as semana'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN estado = "completada" THEN 1 ELSE 0 END) as completadas')
            )
            ->groupBy('semana')
            ->orderBy('semana')
            ->get()
            ->map(fn ($r) => [
                'semana'      => $r->semana,
                'total'       => $r->total,
                'completadas' => $r->completadas,
            ]);

        // ── Próximas citas (las siguientes 3) ────────────────────────────────
        $proximasCitas = Cita::where('profesional_id', $profesional->id)
            ->where('fecha', '>=', today())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->with(['cliente:id,nombre,apellido,telefono', 'servicio:id,nombre'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit(3)
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'referencia'      => $c->codigo_referencia,
                'fecha'           => $c->fecha->toDateString(),
                'hora'            => substr($c->hora_inicio, 0, 5),
                'estado'          => $c->estado,
                'cliente'         => $c->cliente?->nombre_completo,
                'servicio'        => $c->servicio?->nombre,
                'precio'          => (float) $c->precio_total,
            ]);

        // ── Respuesta consolidada ─────────────────────────────────────────────
        return response()->json([
            'success' => true,
            'periodo' => [
                'label'  => $labelPeriodo,
                'inicio' => $inicio->toDateString(),
                'fin'    => $fin->toDateString(),
            ],
            'profesional' => [
                'id'     => $profesional->id,
                'nombre' => $profesional->nombre_completo,
            ],

            // ── Métricas principales ─────────────────────────────────────────
            'citas' => [
                'total'               => $totalCitas,
                'completadas'         => $completadas,
                'canceladas'          => $canceladas,
                'no_asistio'          => $noAsistio,
                'total_cancelaciones' => $totalCancelaciones,
                'pendientes'          => $pendientes,
                'rechazadas'          => $rechazadas,
                'tasa_completado_pct' => $tasaCompletado,
            ],

            // ── Ingresos ─────────────────────────────────────────────────────
            'ingresos' => $ingresosData,

            // ── Comparativa vs periodo anterior ──────────────────────────────
            'comparativa' => [
                'periodo_anterior' => [
                    'inicio'      => $inicioAnterior->toDateString(),
                    'fin'         => $finAnterior->toDateString(),
                    'total_citas' => $totalCitasAnt,
                    'completadas' => $completadasAnt,
                    'ingresos'    => $ingresosAnt['total_cobrado'],
                ],
                'variacion_citas_pct'    => $this->variacionPct($totalCitas, $totalCitasAnt),
                'variacion_ingresos_pct' => $this->variacionPct(
                    $ingresosData['total_cobrado'],
                    $ingresosAnt['total_cobrado']
                ),
            ],

            // ── Distribuciones ────────────────────────────────────────────────
            'distribucion_estados' => $distribucionEstados,
            'citas_por_dia'        => $citasPorDia,
            'evolucion_semanal'    => $evolucionSemanal,

            // ── Rankings ─────────────────────────────────────────────────────
            'top_servicios' => $topServicios,

            // ── Agenda ───────────────────────────────────────────────────────
            'proximas_citas' => $proximasCitas,
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/agenda
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve la agenda del profesional: citas de hoy + próximas 7 días.
     * Ideal para el widget de la app móvil.
     */
    public function agenda(Request $request): JsonResponse
    {
        $profesional = $this->resolverProfesional($request);

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'Profesional no encontrado.'], 403);
        }

        $hoy     = today();
        $en7dias = $hoy->copy()->addDays(7);

        $citas = Cita::where('profesional_id', $profesional->id)
            ->whereBetween('fecha', [$hoy->toDateString(), $en7dias->toDateString()])
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->with([
                'cliente:id,nombre,apellido,telefono,foto',
                'servicio:id,nombre,duracion_min,precio,moneda',
            ])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy(fn ($cita) => $cita->fecha->toDateString())
            ->map(fn ($citasDelDia, $fecha) => [
                'fecha'       => $fecha,
                'dia_label'   => Carbon::parse($fecha)->translatedFormat('l d \d\e F'),
                'total'       => $citasDelDia->count(),
                'citas'       => $citasDelDia->map(fn ($c) => [
                    'id'              => $c->id,
                    'referencia'      => $c->codigo_referencia,
                    'hora_inicio'     => substr($c->hora_inicio, 0, 5),
                    'hora_fin'        => substr($c->hora_fin, 0, 5),
                    'duracion_min'    => $c->duracion_min,
                    'estado'          => $c->estado,
                    'precio'          => (float) $c->precio_total,
                    'moneda'          => $c->moneda,
                    'cliente'         => [
                        'id'     => $c->cliente?->id,
                        'nombre' => $c->cliente?->nombre_completo,
                        'tel'    => $c->cliente?->telefono,
                        'foto'   => $c->cliente?->foto,
                    ],
                    'servicio'        => [
                        'nombre'       => $c->servicio?->nombre,
                        'duracion_min' => $c->servicio?->duracion_min,
                    ],
                    'notas'           => $c->notas_cliente,
                    'cancelable'      => $c->estaCancelable(),
                ])->values(),
            ])->values();

        // Resumen rápido del día
        $hoyStr      = $hoy->toDateString();
        $citasDeHoy  = Cita::where('profesional_id', $profesional->id)
            ->whereDate('fecha', $hoy)
            ->get();

        return response()->json([
            'success' => true,
            'hoy'     => $hoyStr,
            'resumen_hoy' => [
                'total'      => $citasDeHoy->count(),
                'completadas'=> $citasDeHoy->where('estado', 'completada')->count(),
                'pendientes' => $citasDeHoy->whereIn('estado', ['pendiente', 'confirmada'])->count(),
                'ingresos'   => (float) $citasDeHoy->where('estado', 'completada')->sum('precio_total'),
            ],
            'agenda' => $citas,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/resumen-rapido
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Widget compacto para la pantalla de inicio de la app móvil.
     * Respuesta mínima y rápida (ideal para KPIs en tiempo real).
     */
    public function resumenRapido(Request $request): JsonResponse
    {
        $profesional = $this->resolverProfesional($request);

        if (!$profesional) {
            return response()->json(['success' => false], 403);
        }

        $inicioMes = now()->startOfMonth();
        $finMes    = now()->endOfMonth();

        $citasMes = Cita::where('profesional_id', $profesional->id)
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado IN ('cancelada','no_asistio') THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN estado IN ('pendiente','confirmada') THEN 1 ELSE 0 END) as pendientes
            ")
            ->first();

        $ingresosMes = Pago::whereHas('cita', fn ($q) => $q->where('profesional_id', $profesional->id))
            ->whereBetween('pagado_en', [$inicioMes, $finMes])
            ->where('estado', 'completado')
            ->sum('monto_total');

        $proxima = Cita::where('profesional_id', $profesional->id)
            ->where('fecha', '>=', today())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->with(['cliente:id,nombre', 'servicio:id,nombre'])
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->first();

        return response()->json([
            'success' => true,
            'mes'     => now()->translatedFormat('F Y'),
            'kpis' => [
                'total_citas'    => (int)   $citasMes->total,
                'completadas'    => (int)   $citasMes->completadas,
                'canceladas'     => (int)   $citasMes->canceladas,
                'pendientes'     => (int)   $citasMes->pendientes,
                'ingresos_mes'   => (float) $ingresosMes,
                'tasa_exito_pct' => $citasMes->total > 0
                    ? round(($citasMes->completadas / $citasMes->total) * 100, 1)
                    : 0,
            ],
            'proxima_cita' => $proxima ? [
                'id'          => $proxima->id,
                'referencia'  => $proxima->codigo_referencia,
                'fecha'       => $proxima->fecha->toDateString(),
                'hora'        => substr($proxima->hora_inicio, 0, 5),
                'cliente'     => $proxima->cliente?->nombre,
                'servicio'    => $proxima->servicio?->nombre,
            ] : null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resuelve el Profesional a partir del request.
     *
     * El cliente autenticado (via OTP) puede pedir métricas de su profesional
     * si está vinculado, o el propio profesional puede pedir las suyas.
     * Por ahora, usamos el query param profesional_id o el autenticado.
     */
    private function resolverProfesional(Request $request): ?Profesional
    {
        $profesionalId = $request->query('profesional_id');

        if ($profesionalId) {
            return Profesional::where('activo', true)->find($profesionalId);
        }

        // Si el usuario autenticado ES un profesional (futuro: profesionales con token propio)
        // Por ahora devolvemos el primer profesional del negocio del cliente
        // Esto se refinará en la Fase 3 con autenticación de profesionales
        return null;
    }

    /**
     * Calcula los ingresos de un profesional en un rango de fechas.
     */
    private function calcularIngresos(int $profesionalId, Carbon $inicio, Carbon $fin): array
    {
        $pagos = Pago::whereHas('cita', function ($q) use ($profesionalId, $inicio, $fin) {
            $q->where('profesional_id', $profesionalId)
              ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);
        });

        $cobrados  = (clone $pagos)->where('estado', 'completado');
        $pendientes= (clone $pagos)->where('estado', 'pendiente');

        $totalCobrado     = (float) $cobrados->sum('monto_total');
        $totalDescuento   = (float) $cobrados->sum('descuento');
        $totalImpuesto    = (float) $cobrados->sum('impuesto');
        $totalPendiente   = (float) $pendientes->sum('monto_total');

        // También suma los precios de citas completadas sin pago registrado
        $citasSinPago = Cita::where('profesional_id', $profesionalId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->whereDoesntHave('pago')
            ->sum('precio_total');

        return [
            'total_cobrado'    => $totalCobrado,
            'total_pendiente'  => $totalPendiente,
            'citas_sin_pago'   => (float) $citasSinPago, // Ingresos no registrados aún
            'total_descuentos' => $totalDescuento,
            'total_impuestos'  => $totalImpuesto,
            'moneda'           => 'EUR',
        ];
    }

    /**
     * Define el inicio y fin de la ventana de tiempo según el periodo solicitado.
     *
     * @return array{Carbon, Carbon, string}
     */
    private function ventanaTiempo(string $periodo): array
    {
        return match ($periodo) {
            'semana'       => [now()->startOfWeek(), now()->endOfWeek(), 'Esta semana'],
            'mes_anterior' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth(), 'Mes anterior'],
            'anio'         => [now()->startOfYear(), now()->endOfYear(), 'Este año'],
            default        => [now()->startOfMonth(), now()->endOfMonth(), 'Mes actual'],  // mes_actual
        };
    }

    /**
     * Calcula la variación porcentual entre dos valores.
     * Devuelve null si no hay periodo anterior válido.
     */
    private function variacionPct(float|int $actual, float|int $anterior): ?float
    {
        if ($anterior == 0) {
            return $actual > 0 ? 100.0 : null;
        }
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * Convierte el número de día de semana (MySQL DAYOFWEEK: 1=Dom, 7=Sáb)
     * al nombre del día en español.
     */
    private function nombreDiaSemana(int $dia): string
    {
        return match ($dia) {
            1 => 'Domingo',
            2 => 'Lunes',
            3 => 'Martes',
            4 => 'Miércoles',
            5 => 'Jueves',
            6 => 'Viernes',
            7 => 'Sábado',
            default => "Día {$dia}",
        };
    }
}
