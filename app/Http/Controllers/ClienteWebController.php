<?php

namespace App\Http\Controllers;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
