<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Profesional;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\CitasListEmail;

class ExportController extends Controller
{
    /**
     * Exporta el listado de citas a un archivo PDF.
     */
    public function exportarCitasPdf(Request $request)
    {
        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $citas = $this->obtenerCitasFiltradas($request, $profesional);
        
        $pdf = Pdf::loadView('exports.citas_pdf', [
            'citas' => $citas,
            'negocio' => $profesional->negocio,
        ]);

        return $pdf->download('Listado_Citas.pdf');
    }

    /**
     * Envía el listado de citas por correo electrónico.
     */
    public function enviarCitasEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'mensaje' => 'nullable|string|max:500',
            'adjuntar_pdf' => 'boolean'
        ]);

        $profesional = $this->getProfesional($request);
        if (!$profesional) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $citas = $this->obtenerCitasFiltradas($request, $profesional);
        $negocio = $profesional->negocio;
        
        $pdfContent = null;
        if ($request->input('adjuntar_pdf', false)) {
            $pdf = Pdf::loadView('exports.citas_pdf', [
                'citas' => $citas,
                'negocio' => $negocio,
            ]);
            $pdfContent = $pdf->output();
        }

        Mail::to($request->email)->send(new CitasListEmail($citas, $negocio, $request->mensaje, $pdfContent));

        return response()->json([
            'success' => true,
            'message' => 'El correo ha sido enviado correctamente.'
        ]);
    }

    /**
     * Obtiene el listado de citas aplicando los filtros del request.
     */
    private function obtenerCitasFiltradas(Request $request, Profesional $profesional)
    {
        $type = $request->input('type');
        
        $query = Cita::with(['cliente', 'servicio', 'profesional']);
            
        if ($type) {
            $query->where('type', $type);
        }

        // Restricción por rol
        if (in_array($profesional->rol, ['dueño', 'admin'])) {
            $query->where('negocio_id', $profesional->negocio_id);
        } else {
            $query->where('profesional_id', $profesional->id);
        }

        // Filtros básicos
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        return $query->orderBy('fecha', 'desc')->orderBy('hora_inicio')->get();
    }

    private function getProfesional(Request $request): ?Profesional
    {
        $user = $request->user();
        if ($user instanceof Profesional) {
            return $user;
        }

        if ($user instanceof \App\Models\User) {
            return Profesional::first();
        }

        return null;
    }
}
