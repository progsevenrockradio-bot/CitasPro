<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\NegocioDatosFiscales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceApiService
{
    /**
     * Crear una factura con su detalle y lógica VeriFactu.
     *
     * @param array $data
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $negocioId = $data['negocio_id'];
            $serie = $data['serie'];
            
            // 1. Obtener Datos Fiscales del Negocio (Snapshot de emisión)
            $datosFiscales = NegocioDatosFiscales::where('negocio_id', $negocioId)
                ->where('verificado', true)
                ->latest()
                ->first();
                
            if (!$datosFiscales) {
                // Si no hay verificado, tomamos el último disponible
                $datosFiscales = NegocioDatosFiscales::where('negocio_id', $negocioId)
                    ->latest()
                    ->first();
            }

            // 2. Calcular número correlativo en la serie para el negocio
            $ultimoNumero = Invoice::where('negocio_id', $negocioId)
                ->where('serie', $serie)
                ->max(DB::raw('CAST(numero AS UNSIGNED)'));
            
            $siguienteNumero = ($ultimoNumero ?? 0) + 1;
            $numeroFormateado = str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);

            // 3. Evaluar exención de IVA y aplicar reglas según tipo_factura
            $tipoFactura = $data['tipo_factura'];
            $moneda = $data['moneda'];
            $tipoCambio = $data['tipo_cambio'] ?? 1.000000;

            // Instanciar modelo de factura (sin guardar aún)
            $invoice = new Invoice();
            $invoice->negocio_id = $negocioId;
            $invoice->negocio_datos_fiscales_id = $datosFiscales?->id;
            $invoice->cliente_id = $data['cliente_id'] ?? null;
            $invoice->serie = $serie;
            $invoice->numero = $numeroFormateado;
            $invoice->fecha_emision = $data['fecha_emision'];
            $invoice->tipo_factura = $tipoFactura;
            $invoice->moneda = $moneda;
            $invoice->tipo_cambio = $tipoCambio;
            $invoice->datos_cliente_snapshot = $data['datos_cliente_snapshot'];
            $invoice->metadata_adicional = $data['metadata_adicional'] ?? null;
            $invoice->estado = 'emitida';

            // 4. Calcular Totales y procesar líneas
            $subtotalAcumulado = 0;
            $impuestosAcumulados = 0;
            $lineasParaGuardar = [];

            foreach ($data['lines'] as $lineData) {
                $cantidad = (float)$lineData['cantidad'];
                $precioUnitario = (float)$lineData['precio_unitario'];
                $descuentoPorcentaje = (float)($lineData['descuento_porcentaje'] ?? 0);
                
                // Reglas fiscales de IVA
                $ivaPorcentaje = (float)($lineData['iva_porcentaje'] ?? 21.00);
                if ($tipoFactura === 'EXT' || $tipoFactura === 'ROI') {
                    // Operaciones Extracomunitarias o ROI/VIES intracomunitario con inversión de sujeto pasivo
                    $ivaPorcentaje = 0.00;
                }
                
                $irpfPorcentaje = (float)($lineData['irpf_porcentaje'] ?? 0);

                // Cálculo financiero de la línea
                $subtotalLineaOriginal = $cantidad * $precioUnitario;
                $descuentoImporte = $subtotalLineaOriginal * ($descuentoPorcentaje / 100);
                $subtotalLinea = $subtotalLineaOriginal - $descuentoImporte;

                $ivaImporte = $subtotalLinea * ($ivaPorcentaje / 100);
                $irpfImporte = $subtotalLinea * ($irpfPorcentaje / 100);
                
                // Total línea = Subtotal + IVA - IRPF
                $totalLinea = $subtotalLinea + $ivaImporte - $irpfImporte;

                $subtotalAcumulado += $subtotalLinea;
                $impuestosAcumulados += $ivaImporte - $irpfImporte;

                $lineasParaGuardar[] = new InvoiceLine([
                    'descripcion' => $lineData['descripcion'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'irpf_porcentaje' => $irpfPorcentaje,
                    'subtotal' => $subtotalLinea,
                    'impuestos' => $ivaImporte - $irpfImporte,
                    'total' => $totalLinea,
                ]);
            }

            $invoice->subtotal = $subtotalAcumulado;
            $invoice->impuestos = $impuestosAcumulados;
            $invoice->total = $subtotalAcumulado + $impuestosAcumulados;

            // 5. Criptografía y Encadenamiento VeriFactu
            $ultimaFactura = Invoice::where('negocio_id', $negocioId)
                ->where('serie', $serie)
                ->orderBy('id', 'desc')
                ->first();

            $hashAnterior = $ultimaFactura ? $ultimaFactura->hash_actual : null;
            $invoice->hash_anterior = $hashAnterior;

            // Calcular Hash Actual
            // Formato de cadena de encadenamiento VeriFactu simulada
            $cadenaRegistro = sprintf(
                '%d|%s|%s|%s|%.2f|%s',
                $negocioId,
                $serie,
                $numeroFormateado,
                $invoice->fecha_emision->format('Y-m-d H:i:s'),
                $invoice->total,
                $hashAnterior ?? 'FIRST_INVOICE'
            );
            $invoice->hash_actual = hash('sha256', $cadenaRegistro);

            // Simular firma criptográfica
            $invoice->firma = base64_encode(hash_hmac('sha256', $cadenaRegistro, 'citaspro-signing-secret-' . $negocioId, true));

            // Generar datos QR VeriFactu reglamentarios
            $invoice->datos_qr = sprintf(
                'https://citaspro.app/verifactu/qr?nif=%s&serie=%s&num=%s&fecha=%s&total=%.2f&hash=%s',
                urlencode($datosFiscales?->datos_fiscales['nif'] ?? '00000000T'),
                urlencode($serie),
                urlencode($numeroFormateado),
                urlencode($invoice->fecha_emision->format('Y-m-d')),
                $invoice->total,
                urlencode($invoice->hash_actual)
            );

            // Guardar factura principal
            $invoice->save();

            // Guardar las líneas vinculadas
            foreach ($lineasParaGuardar as $linea) {
                $linea->invoice_id = $invoice->id;
                $linea->save();
            }

            return $invoice;
        });
    }
}
