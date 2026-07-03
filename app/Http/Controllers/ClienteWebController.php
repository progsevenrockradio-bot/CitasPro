<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Resena;
use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ClienteWebController extends Controller
{
    /**
     * Muestra el perfil público del profesional para que los clientes reserven.
     */
    public function perfil(int $id): View
    {
        $profesional = Profesional::with([
            'negocio', 
            'servicios' => function($q) {
                $q->where('activo', true);
            },
            'portafolios' => function($q) {
                $q->where('publico', true)
                  ->orderBy('orden', 'asc')
                  ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        if (!$profesional->activo) {
            abort(404, 'Este profesional no está activo actualmente.');
        }

        return view('cliente.perfil', compact('profesional'));
    }

    /**
     * Muestra el formulario de reseñas para una cita específica por su código de referencia.
     */
    public function resenaForm(string $codigo): View
    {
        $cita = Cita::with(['cliente', 'servicio', 'negocio', 'profesional'])
            ->where('codigo_referencia', $codigo)
            ->firstOrFail();

        // Verificar que esté completada
        if ($cita->estado !== 'completada') {
            abort(403, 'Solo se pueden calificar citas completadas.');
        }

        // Verificar si ya tiene reseña
        $resenaExistente = Resena::where('cita_id', $cita->id)->first();
        $yaResenado = $resenaExistente ? true : false;

        return view('cliente.resena', compact('cita', 'yaResenado'));
    }

    /**
     * Guarda la reseña del cliente y recalcula el promedio del profesional.
     */
    public function resenaSubmit(Request $request, string $codigo)
    {
        $request->validate([
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario'   => 'nullable|string|max:1000',
        ]);

        $cita = Cita::with(['cliente', 'profesional'])
            ->where('codigo_referencia', $codigo)
            ->firstOrFail();

        if ($cita->estado !== 'completada') {
            return back()->with('error', 'Solo se pueden calificar citas completadas.');
        }

        $resenaExistente = Resena::where('cita_id', $cita->id)->first();
        if ($resenaExistente) {
            return back()->with('error', 'Esta cita ya ha sido calificada.');
        }

        DB::transaction(function () use ($cita, $request) {
            Resena::create([
                'negocio_id'     => $cita->negocio_id,
                'profesional_id' => $cita->profesional_id,
                'cliente_id'     => $cita->cliente_id,
                'cita_id'        => $cita->id,
                'calificacion'   => $request->calificacion,
                'comentario'     => $request->comentario,
                'activo'         => true,
            ]);

            // Recalcular promedio y total en el profesional
            $estadisticas = Resena::where('profesional_id', $cita->profesional_id)
                ->activo()
                ->selectRaw('count(*) as total, avg(calificacion) as promedio')
                ->first();

            $cita->profesional->update([
                'total_resenas'         => $estadisticas->total ?? 0,
                'calificacion_promedio' => $estadisticas->promedio ?? 0,
            ]);
        });

        return redirect()->route('cliente.resena_form', $codigo)->with('success', '¡Muchas gracias por tu calificación!');
    }
}
