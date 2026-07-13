<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Support\Facades\DB;
use Exception;

class CreateRectificationInvoiceAction
{
    /**
     * Genera una factura rectificativa (abono) invirtiendo importes y enlazando la original.
     *
     * @param int $originalInvoiceId
     * @param string $reason Motivo de la rectificación fiscal.
     * @return Invoice La factura rectificativa generada.
     * @throws Exception Si la factura ya ha sido anulada o no es válida.
     */
    public function execute(int $originalInvoiceId, string $reason): Invoice
    {
        return DB::transaction(function () use ($originalInvoiceId, $reason) {
            // 1. Obtener la factura original
            $original = Invoice::with('lines')->findOrFail($originalInvoiceId);

            if ($original->estado === 'anulada' || $original->estado === 'rectificativa') {
                throw new Exception('No se puede rectificar una factura ya anulada o que es de tipo rectificativa.');
            }

            $negocioId = $original->negocio_id;
            // Definimos la serie de rectificación diferenciada por ley (ej. R-F2026)
            $newSerie = 'R-' . $original->serie;

            // 2. Calcular número correlativo en la serie rectificativa para el negocio
            $ultimoNumero = Invoice::where('negocio_id', $negocioId)
                ->where('serie', $newSerie)
                ->max(DB::raw('CAST(numero AS UNSIGNED)'));
            
            $siguienteNumero = ($ultimoNumero ?? 0) + 1;
            $numeroFormateado = str_pad((string)$siguienteNumero, 6, '0', STR_PAD_LEFT);

            // 3. Instanciar la factura rectificativa
            $rectificativa = new Invoice();
            $rectificativa->negocio_id = $negocioId;
            $rectificativa->negocio_datos_fiscales_id = $original->negocio_datos_fiscales_id;
            $rectificativa->cliente_id = $original->cliente_id;
            $rectificativa->serie = $newSerie;
            $rectificativa->numero = $numeroFormateado;
            $rectificativa->fecha_emision = now();
            $rectificativa->tipo_factura = $original->tipo_factura;
            $rectificativa->moneda = $original->moneda;
            $rectificativa->tipo_cambio = $original->tipo_cambio;
            
            // Invertir importes financieros de la cabecera
            $rectificativa->subtotal = number_format(-$original->subtotal, 2, '.', '');
            $rectificativa->impuestos = number_format(-$original->impuestos, 2, '.', '');
            $rectificativa->total = number_format(-$original->total, 2, '.', '');
            
            $rectificativa->estado = 'rectificativa';
            $rectificativa->rectifies_invoice_id = $original->id;
            $rectificativa->rectification_reason = $reason;
            
            $rectificativa->datos_cliente_snapshot = $original->datos_cliente_snapshot;
            
            $originalMetadata = $original->metadata_adicional ?? [];
            $rectificativa->metadata_adicional = array_merge($originalMetadata, [
                'is_rectification' => true,
                'original_invoice_id' => $original->id,
                'original_invoice_number' => $original->serie . '-' . $original->numero,
            ]);

            // 4. Calcular Hash Anterior y Encadenamiento VeriFactu
            $ultimaRectificativa = Invoice::where('negocio_id', $negocioId)
                ->where('serie', $newSerie)
                ->orderBy('id', 'desc')
                ->first();

            $hashAnterior = $ultimaRectificativa ? $ultimaRectificativa->hash_actual : null;
            $rectificativa->hash_anterior = $hashAnterior;

            // Formato de cadena de encadenamiento VeriFactu
            $cadenaRegistro = sprintf(
                '%d|%s|%s|%s|%.2f|%s',
                $negocioId,
                $newSerie,
                $numeroFormateado,
                $rectificativa->fecha_emision->format('Y-m-d H:i:s'),
                $rectificativa->total,
                $hashAnterior ?? 'FIRST_INVOICE'
            );
            $rectificativa->hash_actual = hash('sha256', $cadenaRegistro);

            // Firma criptográfica del nuevo hash
            $rectificativa->firma = base64_encode(hash_hmac('sha256', $cadenaRegistro, 'citaspro-signing-secret-' . $negocioId, true));

            // Generar datos QR VeriFactu reglamentarios
            $nif = $original->negocioDatosFiscales?->datos_fiscales['nif'] ?? '00000000T';
            $rectificativa->datos_qr = sprintf(
                'https://citaspro.app/verifactu/qr?nif=%s&serie=%s&num=%s&fecha=%s&total=%.2f&hash=%s',
                urlencode($nif),
                urlencode($newSerie),
                urlencode($numeroFormateado),
                urlencode($rectificativa->fecha_emision->format('Y-m-d')),
                $rectificativa->total,
                urlencode($rectificativa->hash_actual)
            );

            // Guardar factura rectificativa
            $rectificativa->save();

            // 5. Invertir y guardar cada una de las líneas detalladas
            foreach ($original->lines as $line) {
                $rectLine = new InvoiceLine([
                    'descripcion' => 'Rectificación: ' . $line->descripcion,
                    'cantidad' => $line->cantidad,
                    'precio_unitario' => -$line->precio_unitario,
                    'descuento_porcentaje' => $line->descuento_porcentaje,
                    'iva_porcentaje' => $line->iva_porcentaje,
                    'irpf_porcentaje' => $line->irpf_porcentaje,
                    'subtotal' => -$line->subtotal,
                    'impuestos' => -$line->impuestos,
                    'total' => -$line->total,
                ]);
                $rectLine->invoice_id = $rectificativa->id;
                $rectLine->save();
            }

            // 6. Marcar la factura original como anulada (rectificada) contablemente
            $original->update([
                'estado' => 'anulada',
                'metadata_adicional' => array_merge($originalMetadata, [
                    'rectified_by_invoice_id' => $rectificativa->id,
                    'rectified_reason' => $reason
                ])
            ]);

            return $rectificativa;
        });
    }
}
