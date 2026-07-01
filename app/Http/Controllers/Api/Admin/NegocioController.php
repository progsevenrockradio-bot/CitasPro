<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Negocio;

class NegocioController extends Controller
{
    /**
     * Listar todos los negocios en la plataforma.
     */
    public function index(Request $request)
    {
        $negocios = Negocio::with('categoria')->paginate(20);
        return response()->json($negocios);
    }

    /**
     * Ver el detalle de un negocio específico.
     */
    public function show(Negocio $negocio)
    {
        $negocio->load(['categoria', 'profesionales']);
        return response()->json($negocio);
    }

    /**
     * Actualizar estado o plan de un negocio (Suspender/Activar).
     */
    public function update(Request $request, Negocio $negocio)
    {
        $validated = $request->validate([
            'activo' => 'sometimes|boolean',
            'plan'   => 'sometimes|in:free,basic,pro,enterprise',
            'verificado' => 'sometimes|boolean',
        ]);

        $negocio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Negocio actualizado correctamente.',
            'negocio' => $negocio
        ]);
    }
}
