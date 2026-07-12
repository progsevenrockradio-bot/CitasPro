<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Services\InvoiceApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected InvoiceApiService $invoiceService;

    public function __construct(InvoiceApiService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Emitir una nueva factura bajo la normativa VeriFactu.
     *
     * @param StoreInvoiceRequest $request
     * @return JsonResponse
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Factura emitida y registrada en VeriFactu correctamente.',
                'data' => $invoice->load('lines')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al emitir factura VeriFactu: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar la factura.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
