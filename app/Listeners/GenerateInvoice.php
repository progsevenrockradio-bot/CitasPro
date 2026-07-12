<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Services\InvoiceApiService;
use Illuminate\Support\Facades\Log;

class GenerateInvoice
{
    protected InvoiceApiService $invoiceService;

    public function __construct(InvoiceApiService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function handle(AppointmentCompleted $event): void
    {
        $cita = $event->cita;
        $cliente = $cita->cliente;
        $servicio = $cita->servicio;

        if (!$cliente) {
            Log::warning("No se pudo generar factura para la cita {$cita->id} porque no tiene cliente asociado.");
            return;
        }

        // Snapshot de los datos del cliente
        $datosClienteSnapshot = [
            'nombre' => trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellido ?? '')),
            'nif' => $cliente->nif ?? '',
            'email' => $cliente->email ?? '',
            'telefono' => $cliente->telefono ?? '',
        ];

        if (empty($datosClienteSnapshot['nombre'])) {
            $datosClienteSnapshot['nombre'] = 'Cliente Genérico';
        }

        // Determinar tipo de factura
        $tipoFactura = 'B2C'; 
        if (!empty($cliente->nif)) {
            $tipoFactura = 'B2B'; // Factura ordinaria nominativa
        }

        $lines = [
            [
                'descripcion' => $servicio ? $servicio->nombre : 'Servicio CitasPro',
                'cantidad' => 1,
                'precio_unitario' => $cita->precio_total,
                'iva_porcentaje' => $servicio ? ($servicio->iva_porcentaje ?? 21.00) : 21.00,
                'descuento_porcentaje' => 0,
                'irpf_porcentaje' => 0,
            ]
        ];

        $invoiceData = [
            'negocio_id' => $cita->negocio_id,
            'serie' => 'A',
            'cliente_id' => $cita->cliente_id,
            'fecha_emision' => now(),
            'tipo_factura' => $tipoFactura,
            'moneda' => $cita->moneda ?? 'EUR',
            'tipo_cambio' => 1.000000,
            'datos_cliente_snapshot' => $datosClienteSnapshot,
            'lines' => $lines,
            'metadata_adicional' => [
                'cita_id' => $cita->id,
                'cita_codigo' => $cita->codigo_referencia
            ]
        ];

        try {
            $invoice = $this->invoiceService->createInvoice($invoiceData);
            Log::info("Factura generada automáticamente para la cita {$cita->id}: ID Factura {$invoice->id}");
        } catch (\Exception $e) {
            Log::error("Error al generar la factura para la cita {$cita->id}: " . $e->getMessage());
        }
    }
}
