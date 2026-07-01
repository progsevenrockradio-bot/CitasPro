<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profesional;

class ProfesionalController extends Controller
{
    /**
     * Listar todos los profesionales.
     */
    public function index(Request $request)
    {
        $profesionales = Profesional::with('negocio')->paginate(20);
        return response()->json($profesionales);
    }

    /**
     * Ver el detalle de un profesional.
     */
    public function show(Profesional $profesional)
    {
        $profesional->load('negocio');
        return response()->json($profesional);
    }

    /**
     * Suspender o activar un profesional.
     */
    public function update(Request $request, Profesional $profesional)
    {
        $validated = $request->validate([
            'activo' => 'sometimes|boolean',
        ]);

        $profesional->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profesional actualizado correctamente.',
            'profesional' => $profesional
        ]);
    }
}
