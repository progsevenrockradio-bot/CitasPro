<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Estado;
use App\Models\Pais;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * GET /api/locations/states/{pais_id}
     * Lista de estados de un país
     */
    public function states(int $pais_id): JsonResponse
    {
        // Cacheamos por 24h ya que esto no cambia frecuentemente
        $estados = cache()->remember("states_pais_{$pais_id}", 86400, function () use ($pais_id) {
            return Estado::where('pais_id', $pais_id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo']);
        });

        return response()->json([
            'success' => true,
            'data' => $estados
        ]);
    }

    /**
     * GET /api/locations/cities/{estado_id}
     * Lista de ciudades de un estado
     */
    public function cities(int $estado_id): JsonResponse
    {
        $ciudades = cache()->remember("cities_estado_{$estado_id}", 86400, function () use ($estado_id) {
            return \App\Models\Ciudad::where('estado_id', $estado_id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'es_capital']);
        });

        return response()->json([
            'success' => true,
            'data' => $ciudades
        ]);
    }
}
