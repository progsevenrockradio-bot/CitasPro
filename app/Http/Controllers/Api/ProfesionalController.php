<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfesionalController extends Controller
{
    /**
     * Devuelve los profesionales del negocio del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $owner = $this->getOwner($user);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos para ver esto.'], 403);
        }

        $profesionales = Profesional::where('negocio_id', $owner->negocio_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'profesionales' => $profesionales
        ]);
    }

    /**
     * Crea un nuevo profesional en el negocio.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $owner = $this->getOwner($user);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'telefono' => 'required|string|max:20|unique:profesionales,telefono',
            'email' => 'required|email|max:150|unique:profesionales,email',
            'especialidad' => 'nullable|string|max:150',
            'activo' => 'boolean'
        ]);

        $profesional = Profesional::create([
            'negocio_id' => $owner->negocio_id,
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'telefono' => $validated['telefono'],
            'email' => $validated['email'],
            'especialidad' => $validated['especialidad'] ?? null,
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profesional creado correctamente.',
            'profesional' => $profesional
        ]);
    }

    /**
     * Actualiza un profesional existente.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $owner = $this->getOwner($user);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $profesional = Profesional::where('negocio_id', $owner->negocio_id)->findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:150',
            'apellido' => 'sometimes|string|max:150',
            'telefono' => 'sometimes|string|max:20|unique:profesionales,telefono,' . $id,
            'email' => 'sometimes|email|max:150|unique:profesionales,email,' . $id,
            'especialidad' => 'nullable|string|max:150',
            'activo' => 'sometimes|boolean'
        ]);

        $profesional->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profesional actualizado correctamente.',
            'profesional' => $profesional
        ]);
    }

    /**
     * Elimina (o desactiva) un profesional.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $owner = $this->getOwner($user);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
        }

        $profesional = Profesional::where('negocio_id', $owner->negocio_id)->findOrFail($id);
        
        // Soft delete
        $profesional->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profesional eliminado correctamente.'
        ]);
    }
    
    private function getOwner($user)
    {
        // MODO DEMO
        if ($user instanceof User) {
            $profesional = Profesional::first();
            if ($profesional) {
                $profesional->rol = 'dueño';
                return $profesional;
            }
        }
        
        if ($user instanceof Profesional && in_array($user->rol, ['dueño', 'admin'])) {
            return $user;
        }
        
        return null;
    }
}
