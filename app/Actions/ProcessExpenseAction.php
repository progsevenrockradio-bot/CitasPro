<?php

namespace App\Actions;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ProcessExpenseAction
{
    /**
     * Procesa y registra un gasto aplicando reglas de deducibilidad y amortización.
     *
     * @param array $data
     * @return Expense
     */
    public function execute(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $subtotal = (float)$data['subtotal'];
            $ivaPorcentaje = (float)($data['iva_porcentaje'] ?? 21.00);
            $categoria = $data['categoria'];
            $afectacionPorcentaje = (float)($data['afectacion_porcentaje'] ?? 100.00);

            // Calcular impuestos y total
            $impuestos = $subtotal * ($ivaPorcentaje / 100);
            $total = $subtotal + $impuestos;

            // Determinar si es un Bien de Inversión (requiere amortización si es > 300€)
            // Según la normativa del IRPF/IVA, bienes de uso duradero superiores a 300€ (a veces 3005.06€ en IVA, pero 300€ es el límite general de amortización rápida)
            $esBienInversion = $subtotal > 300.00;

            // Aplicar regla de deducibilidad
            if (strtolower($categoria) === 'home office' || strtolower($categoria) === 'home_office') {
                // Regla AEAT: 30% sobre el porcentaje de la vivienda afecta a la actividad
                $importeDeducible = $subtotal * ($afectacionPorcentaje / 100) * 0.30;
            } else {
                // Gastos de Explotación, Dietas u otros: deducibles al porcentaje de afectación directa
                $importeDeducible = $subtotal * ($afectacionPorcentaje / 100);
            }

            // Crear el registro de gasto
            $expense = Expense::create([
                'negocio_id'             => $data['negocio_id'],
                'proveedor_nombre'       => $data['proveedor_nombre'] ?? 'Proveedor Desconocido',
                'proveedor_nif'          => $data['proveedor_nif'] ?? null,
                'concepto'               => $data['concepto'],
                'fecha_gasto'            => $data['fecha_gasto'] ?? now(),
                'categoria'              => $categoria,
                'subtotal'               => $subtotal,
                'iva_porcentaje'         => $ivaPorcentaje,
                'impuestos'              => $impuestos,
                'total'                  => $total,
                'afectacion_porcentaje'  => $afectacionPorcentaje,
                'importe_deducible'      => $importeDeducible,
                'es_bien_inversion'      => $esBienInversion,
                'documento_adjunto_path' => $data['documento_adjunto_path'] ?? null,
                'detalles_categoria'     => $data['detalles_categoria'] ?? null,
                'detalles_opcionales'    => $data['detalles_opcionales'] ?? null,
            ]);

            return $expense;
        });
    }
}
