<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServicioController extends Controller
{
    /**
     * Devuelve los servicios del profesional autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes un perfil de profesional activo.'], 403);
        }

        $servicios = Servicio::where('negocio_id', $profesional->negocio_id)
            ->orderBy('orden', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'servicios' => $servicios
        ]);
    }

    /**
     * Crea un nuevo servicio.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes un perfil de profesional activo.'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:1',
            'activo' => 'boolean'
        ]);

        $servicio = Servicio::create([
            'negocio_id' => $profesional->negocio_id,
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'precio' => $validated['precio'],
            'duracion_min' => $validated['duracion_min'],
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio creado correctamente.',
            'servicio' => $servicio
        ]);
    }

    /**
     * Actualiza un servicio existente.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $servicio = Servicio::where('negocio_id', $profesional->negocio_id)->findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|numeric|min:0',
            'duracion_min' => 'sometimes|integer|min:1',
            'activo' => 'sometimes|boolean'
        ]);

        $servicio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'servicio' => $servicio
        ]);
    }

    /**
     * Elimina (o desactiva) un servicio.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $profesional = $user instanceof Profesional ? $user : null;

        if (!$profesional) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $servicio = Servicio::where('negocio_id', $profesional->negocio_id)->findOrFail($id);
        
        // Soft delete
        $servicio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente.'
        ]);
    }
}
