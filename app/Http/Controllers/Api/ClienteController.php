<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\Profesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes del negocio del profesional autenticado.
     * Búsqueda: ?buscar=juan  (nombre o teléfono)
     */
    public function index(Request $request): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        // Clientes que han tenido al menos una cita con este profesional/negocio
        $query = Cliente::whereHas('citas', function ($q) use ($profesional) {
                $q->where('negocio_id', $profesional->negocio_id);
            })
            ->withCount('citas')
            ->withSum('pagos', 'monto_total');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('telefono', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query->orderBy('nombre')->paginate(20);

        return response()->json($clientes);
    }

    /**
     * Ver el perfil completo de un cliente: historial de citas, total gastado y notas.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $cliente = Cliente::findOrFail($id);

        // Verificar que el cliente pertenezca al negocio
        $tieneCitas = Cita::where('cliente_id', $cliente->id)
            ->where('negocio_id', $profesional->negocio_id)
            ->exists();

        if (!$tieneCitas) {
            return response()->json(['message' => 'Cliente no encontrado en este negocio.'], 404);
        }

        // Historial de citas en este negocio
        $historialCitas = Cita::with('servicio')
            ->where('cliente_id', $cliente->id)
            ->where('negocio_id', $profesional->negocio_id)
            ->orderBy('fecha', 'desc')
            ->get();

        // Total gastado
        $totalGastado = $historialCitas->where('estado', 'completada')->sum('precio_total');

        // Notas internas (campo hidden por defecto, lo exponemos aquí intencionalmente)
        return response()->json([
            'cliente' => [
                'id'             => $cliente->id,
                'nombre'         => $cliente->nombre,
                'apellido'       => $cliente->apellido,
                'telefono'       => $cliente->telefono,
                'email'          => $cliente->email,
                'foto'           => $cliente->foto,
                'notas_internas' => $cliente->notas_internas,
                'condiciones_medicas' => $cliente->condiciones_medicas,
                'created_at'     => $cliente->created_at,
            ],
            'estadisticas' => [
                'total_citas'    => $historialCitas->count(),
                'citas_completadas' => $historialCitas->where('estado', 'completada')->count(),
                'total_gastado'  => round($totalGastado, 2),
            ],
            'historial_citas' => $historialCitas,
        ]);
    }

    /**
     * Actualizar las notas internas de un cliente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $cliente = Cliente::findOrFail($id);

        // Verificar que el cliente pertenezca al negocio
        $tieneCitas = Cita::where('cliente_id', $cliente->id)
            ->where('negocio_id', $profesional->negocio_id)
            ->exists();

        if (!$tieneCitas) {
            return response()->json(['message' => 'Cliente no encontrado en este negocio.'], 404);
        }

        $validated = $request->validate([
            'notas_internas' => 'nullable|string|max:1000',
            'condiciones_medicas' => 'nullable|string|max:1000',
        ]);

        $cliente->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notas del cliente actualizadas.',
        ]);
    }

    private function getProfesional(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional) {
            return $user;
        }
        
        // MODO DEMO: Si el usuario es el Super Admin (modelo User), le damos el primer profesional
        if ($user instanceof \App\Models\User) {
            return Profesional::first();
        }

        return null;
    }
}
