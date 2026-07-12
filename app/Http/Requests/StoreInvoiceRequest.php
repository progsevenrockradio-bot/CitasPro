<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'negocio_id' => 'required|exists:negocios,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'serie' => 'required|string|max:50',
            'fecha_emision' => 'required|date_format:Y-m-d H:i:s',
            'tipo_factura' => 'required|in:B2B,B2C,ROI,EXT',
            
            // Multimoneda
            'moneda' => 'required|string|size:3',
            'tipo_cambio' => 'nullable|numeric|min:0.000001',

            // Snapshot del cliente receptor
            'datos_cliente_snapshot' => 'required|array',
            'datos_cliente_snapshot.nombre' => 'required|string|max:255',
            'datos_cliente_snapshot.nif' => 'required|string|max:50',
            'datos_cliente_snapshot.direccion' => 'required|string|max:255',
            'datos_cliente_snapshot.ciudad' => 'required|string|max:255',
            'datos_cliente_snapshot.codigo_postal' => 'required|string|max:20',
            'datos_cliente_snapshot.pais_codigo' => 'required|string|size:2', // ej. ES, FR, US

            // Líneas de la factura
            'lines' => 'required|array|min:1',
            'lines.*.descripcion' => 'required|string|max:255',
            'lines.*.cantidad' => 'required|numeric|min:0.0001',
            'lines.*.precio_unitario' => 'required|numeric|min:0',
            'lines.*.descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'lines.*.iva_porcentaje' => 'nullable|numeric|min:0|max:100',
            'lines.*.irpf_porcentaje' => 'nullable|numeric|min:0|max:100',

            'metadata_adicional' => 'nullable|array',
        ];
    }
}
