<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxCalculationService
{
    /**
     * Calcular porcentaje de prorrata de IVA general para un año.
     * prorrata = (Operaciones con derecho a deducción / Operaciones totales) * 100
     * (Redondeado al número entero superior).
     */
    public function calcularProrrataPorcentaje(int $negocioId, int $anio): int
    {
        // Operaciones con derecho a deducción: B2B, ROI, EXT
        $conDerechoDeduccion = Invoice::where('negocio_id', $negocioId)
            ->whereYear('fecha_emision', $anio)
            ->whereIn('tipo_factura', ['B2B', 'ROI', 'EXT'])
            ->sum('subtotal');

        // Operaciones totales
        $totalVentas = Invoice::where('negocio_id', $negocioId)
            ->whereYear('fecha_emision', $anio)
            ->sum('subtotal');

        if ($totalVentas <= 0) {
            return 100; // Por defecto 100% si no hay facturación
        }

        $porcentaje = ($conDerechoDeduccion / $totalVentas) * 100;

        return (int) ceil($porcentaje);
    }

    /**
     * Calcular la deducción del Home Office para un gasto de suministro.
     * Regla AEAT: Metros afectos / Metros totales, y a ese resultado se le aplica un 30%.
     */
    public function calcularGastoHomeOfficeDeducible(float $subtotal, float $metrosTotales, float $metrosAfectos): float
    {
        if ($metrosTotales <= 0 || $metrosAfectos <= 0) {
            return 0.00;
        }

        $ratioMetros = $metrosAfectos / $metrosTotales;
        $afectacionSuministros = $ratioMetros * 0.30;

        return round($subtotal * $afectacionSuministros, 4);
    }

    /**
     * Generar datos para el Modelo 303 (Declaración de IVA trimestral/mensual).
     */
    public function generarModelo303(int $negocioId, int $ejercicio, string $periodo): array
    {
        $fechas = $this->getFechasPeriodo($ejercicio, $periodo);
        $prorrata = $this->calcularProrrataPorcentaje($negocioId, $ejercicio);

        // --- IVA DEVENGADO (VENTAS) ---
        // Agrupar facturas por tipo de IVA para evitar consultas N+1
        $ventasPorIva = DB::table('invoices')
            ->join('invoice_lines', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.negocio_id', $negocioId)
            ->whereBetween('invoices.fecha_emision', [$fechas['inicio'], $fechas['fin']])
            ->whereNull('invoices.deleted_at')
            ->select('invoice_lines.iva_porcentaje', DB::raw('SUM(invoice_lines.subtotal) as base'), DB::raw('SUM(invoice_lines.impuestos) as cuota'))
            ->groupBy('invoice_lines.iva_porcentaje')
            ->get();

        $ivaDevengadoBases = 0;
        $ivaDevengadoCuotas = 0;
        $devengadoDetalle = [];

        foreach ($ventasPorIva as $v) {
            $base = (float)$v->base;
            $cuota = (float)$v->cuota;
            
            $ivaDevengadoBases += $base;
            $ivaDevengadoCuotas += $cuota;

            $devengadoDetalle[] = [
                'tipo_porcentaje' => (float)$v->iva_porcentaje,
                'base_imponible' => $base,
                'cuota' => $cuota
            ];
        }

        // --- IVA DEDUCIBLE (GASTOS) ---
        // Lazy Collection / Cursor para procesar de forma óptima grandes cantidades de gastos
        $gastosQuery = Expense::where('negocio_id', $negocioId)
            ->whereBetween('fecha_gasto', [$fechas['inicio'], $fechas['fin']])
            ->lazy();

        $ivaDeducibleBaseBienesCorrientes = 0;
        $ivaDeducibleCuotaBienesCorrientes = 0;
        $ivaDeducibleBaseBienesInversion = 0;
        $ivaDeducibleCuotaBienesInversion = 0;

        foreach ($gastosQuery as $gasto) {
            $base = (float)$gasto->subtotal;
            // Aplicar prorrata a la cuota soportada
            $cuota = ((float)$gasto->impuestos) * ($prorrata / 100);

            if ($gasto->es_bien_inversion) {
                $ivaDeducibleBaseBienesInversion += $base;
                $ivaDeducibleCuotaBienesInversion += $cuota;
            } else {
                $ivaDeducibleBaseBienesCorrientes += $base;
                $ivaDeducibleCuotaBienesCorrientes += $cuota;
            }
        }

        $totalDeducible = $ivaDeducibleCuotaBienesCorrientes + $ivaDeducibleCuotaBienesInversion;
        $resultado = $ivaDevengadoCuotas - $totalDeducible;

        return [
            'modelo' => '303',
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'prorrata_aplicada' => $prorrata,
            'devengado' => [
                'base_imponible_total' => $ivaDevengadoBases,
                'cuota_total' => $ivaDevengadoCuotas,
                'desglose' => $devengadoDetalle
            ],
            'deducible' => [
                'bienes_corrientes' => [
                    'base_imponible' => $ivaDeducibleBaseBienesCorrientes,
                    'cuota' => $ivaDeducibleCuotaBienesCorrientes
                ],
                'bienes_inversion' => [
                    'base_imponible' => $ivaDeducibleBaseBienesInversion,
                    'cuota' => $ivaDeducibleCuotaBienesInversion
                ],
                'cuota_total' => $totalDeducible
            ],
            'resultado_declaracion' => $resultado,
            'tipo_resultado' => $resultado >= 0 ? 'ingresar' : 'devolver_compensar'
        ];
    }

    /**
     * Generar datos para el Modelo 130 (Pago fraccionado de IRPF).
     * Se acumulan los ingresos y gastos desde el 1 de enero hasta el final del trimestre actual.
     */
    public function generarModelo130(int $negocioId, int $ejercicio, string $periodo): array
    {
        // El modelo 130 es acumulativo desde el 1 de enero
        $fechaInicioAnio = Carbon::create($ejercicio, 1, 1, 0, 0, 0)->format('Y-m-d H:i:s');
        $fechasPeriodo = $this->getFechasPeriodo($ejercicio, $periodo);
        $fechaFinPeriodo = $fechasPeriodo['fin'];

        // Ingresos de explotación (Subtotal facturado)
        $ingresosAcumulados = Invoice::where('negocio_id', $negocioId)
            ->whereBetween('fecha_emision', [$fechaInicioAnio, $fechaFinPeriodo])
            ->sum('subtotal');

        // Gastos fiscalmente deducibles (Utilizando el importe deducible ya calculado según Home Office / afectación)
        $gastosAcumulados = Expense::where('negocio_id', $negocioId)
            ->whereBetween('fecha_gasto', [$fechaInicioAnio, $fechaFinPeriodo])
            ->sum('importe_deducible');

        $rendimientoNeto = $ingresosAcumulados - $gastosAcumulados;
        
        // Pago fraccionado (20% del rendimiento neto)
        $pagoFraccionado = $rendimientoNeto > 0 ? ($rendimientoNeto * 0.20) : 0.00;

        return [
            'modelo' => '130',
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'ingresos_acumulados' => (float)$ingresosAcumulados,
            'gastos_acumulados' => (float)$gastosAcumulados,
            'rendimiento_neto' => (float)$rendimientoNeto,
            'cuota_20_porciento' => (float)$pagoFraccionado,
            'resultado_declaracion' => (float)$pagoFraccionado
        ];
    }

    /**
     * Generar datos para el Modelo 111 (Retenciones a profesionales y empleados).
     */
    public function generarModelo111(int $negocioId, int $ejercicio, string $periodo): array
    {
        $fechas = $this->getFechasPeriodo($ejercicio, $periodo);

        // Agrupación de gastos recibidos con retención de IRPF (ej. alquileres o profesionales autónomos subcontratados)
        $retencionesGastos = DB::table('expenses')
            ->where('negocio_id', $negocioId)
            ->whereBetween('fecha_gasto', [$fechas['inicio'], $fechas['fin']])
            ->whereNull('deleted_at')
            ->where('detalles_categoria->irpf_retencion', '>', 0)
            ->select(
                DB::raw('COUNT(*) as total_perceptores'),
                DB::raw('SUM(subtotal) as rendimiento_total'),
                DB::raw('SUM(impuestos * (afectacion_porcentaje/100)) as retenciones_totales') // Simulado o extraído de base
            )
            ->first();

        // En CitasPro, si aplicamos IRPF a nuestras propias facturas emitidas, se declararían en el 130. 
        // El 111 es para lo retenido a otros.
        return [
            'modelo' => '111',
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'profesionales_y_empleados' => [
                'perceptores' => (int)($retencionesGastos?->total_perceptores ?? 0),
                'rendimientos' => (float)($retencionesGastos?->rendimiento_total ?? 0.00),
                'retenciones' => (float)($retencionesGastos?->retenciones_totales ?? 0.00)
            ],
            'resultado_declaracion' => (float)($retencionesGastos?->retenciones_totales ?? 0.00)
        ];
    }

    /**
     * Generar datos para el Modelo 349 (Declaración recapitulativa de operaciones intracomunitarias).
     */
    public function generarModelo349(int $negocioId, int $ejercicio, string $periodo): array
    {
        $fechas = $this->getFechasPeriodo($ejercicio, $periodo);

        // Agrupar facturas ROI/VIES emitidas por cliente
        $operacionesRoi = Invoice::where('negocio_id', $negocioId)
            ->where('tipo_factura', 'ROI')
            ->whereBetween('fecha_emision', [$fechas['inicio'], $fechas['fin']])
            ->lazy()
            ->groupBy(function ($invoice) {
                return $invoice->datos_cliente_snapshot['nif'];
            });

        $declarados = [];
        foreach ($operacionesRoi as $nif => $invoicesGroup) {
            $first = $invoicesGroup->first();
            $sumaSubtotals = $invoicesGroup->sum('subtotal');

            $declarados[] = [
                'nif' => $nif,
                'nombre' => $first->datos_cliente_snapshot['nombre'],
                'pais_codigo' => $first->datos_cliente_snapshot['pais_codigo'],
                'clave_operacion' => 'E', // E = Entrega intracomunitaria de servicios
                'importe' => $sumaSubtotals
            ];
        }

        return [
            'modelo' => '349',
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'operaciones' => $declarados,
            'total_operaciones' => count($declarados)
        ];
    }

    /**
     * Helper para obtener fechas de inicio y fin según el trimestre o mes.
     */
    protected function getFechasPeriodo(int $ejercicio, string $periodo): array
    {
        switch ($periodo) {
            case '1T':
                return [
                    'inicio' => Carbon::create($ejercicio, 1, 1, 0, 0, 0)->format('Y-m-d H:i:s'),
                    'fin' => Carbon::create($ejercicio, 3, 31, 23, 59, 59)->format('Y-m-d H:i:s')
                ];
            case '2T':
                return [
                    'inicio' => Carbon::create($ejercicio, 4, 1, 0, 0, 0)->format('Y-m-d H:i:s'),
                    'fin' => Carbon::create($ejercicio, 6, 30, 23, 59, 59)->format('Y-m-d H:i:s')
                ];
            case '3T':
                return [
                    'inicio' => Carbon::create($ejercicio, 7, 1, 0, 0, 0)->format('Y-m-d H:i:s'),
                    'fin' => Carbon::create($ejercicio, 9, 30, 23, 59, 59)->format('Y-m-d H:i:s')
                ];
            case '4T':
                return [
                    'inicio' => Carbon::create($ejercicio, 10, 1, 0, 0, 0)->format('Y-m-d H:i:s'),
                    'fin' => Carbon::create($ejercicio, 12, 31, 23, 59, 59)->format('Y-m-d H:i:s')
                ];
            default:
                // Tratamiento mensual ("01" - "12")
                $mes = (int)$periodo;
                $inicio = Carbon::create($ejercicio, $mes, 1, 0, 0, 0);
                return [
                    'inicio' => $inicio->format('Y-m-d H:i:s'),
                    'fin' => $inicio->copy()->endOfMonth()->format('Y-m-d H:i:s')
                ];
        }
    }
}
