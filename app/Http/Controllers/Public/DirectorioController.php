<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DirectorioController extends Controller
{
    /**
     * GET /api/directorio
     * Lista paginada de negocios activos para la vitrina pública de CitasPro
     * con soporte de rotación inteligente, búsqueda fuzzy y geolocalización.
     */
    public function index(Request $request): JsonResponse
    {
        $q = $request->input('q');
        $categoriaId = $request->input('categoria_id');
        $seed = $request->input('seed', mt_rand(1, 9999));
        
        // Parámetros de Ubicación explícitos (Manual)
        $paisId = $request->input('pais_id');
        $estadoId = $request->input('estado_id');
        $ciudadId = $request->input('ciudad_id');
        
        // Coordenadas GPS directas (Nivel 1)
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        
        // Filtros adicionales
        $filterVerificado = $request->boolean('verificado');
        $filterReservaInmediata = $request->boolean('reserva_inmediata');
        $filterRecomendado = $request->boolean('recomendado'); // Destacados o con buen plan
        $filterMejorValorado = $request->boolean('mejor_valorado');
        $filterMasCercano = $request->boolean('mas_cercano');

        // 1. Geolocalización por IP si no hay GPS ni ubicación manual (Nivel 2)
        $ipLocation = [];
        if (!$lat && !$lng && !$paisId && !$estadoId && !$ciudadId) {
            $ipLocation = $this->geolocateByIp($request->ip());
            if (!empty($ipLocation)) {
                $lat = $ipLocation['latitud'] ?? null;
                $lng = $ipLocation['longitud'] ?? null;
                
                // Intentamos buscar en nuestra BD
                if (isset($ipLocation['pais_codigo'])) {
                    $pais = DB::table('paises')->where('codigo_iso2', $ipLocation['pais_codigo'])->first();
                    if ($pais) {
                        $paisId = $pais->id;
                        if (isset($ipLocation['region'])) {
                            $estado = DB::table('estados')->where('pais_id', $pais->id)->where('nombre', 'like', "%{$ipLocation['region']}%")->first();
                            if ($estado) {
                                $estadoId = $estado->id;
                                if (isset($ipLocation['ciudad'])) {
                                    $ciudad = DB::table('ciudades')->where('estado_id', $estado->id)->where('nombre', 'like', "%{$ipLocation['ciudad']}%")->first();
                                    if ($ciudad) {
                                        $ciudadId = $ciudad->id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 2. Procesamiento de búsqueda inteligente (Fuzzy + Synonyms)
        $searchTerms = [];
        $queryCorrected = null;
        if ($q) {
            $searchResult = $this->processFuzzySearchAndSynonyms($q);
            $searchTerms = $searchResult['search_terms'];
            $queryCorrected = $searchResult['corrected_query'];
        }

        // Iniciar Query
        $query = Negocio::with(['categoria'])
            ->withAvg('resenas', 'calificacion')
            ->withCount('resenas')
            ->withCount('citas')
            ->where('activo', true)
            ->where('booking_activo', true);

        // Filtro por categoría
        if ($categoriaId) {
            $query->where('categoria_id', $categoriaId);
        }

        // Filtros premium
        if ($filterVerificado) {
            $query->where('verificado', true);
        }
        if ($filterReservaInmediata) {
            $query->where('booking_activo', true);
        }
        if ($filterRecomendado) {
            $query->where(function($query) {
                $query->where('destacado', true)
                      ->orWhereIn('plan', ['enterprise', 'pro']);
            });
        }
        if ($filterMejorValorado) {
            // Solo negocios con promedio de reseñas >= 4.0
            $query->having('resenas_avg_calificacion', '>=', 4.0);
        }

        // 3. Scoring de Relevancia (si hay búsqueda)
        $relevanceSelect = '';
        if ($q && !empty($searchTerms)) {
            $scoringCases = [];
            
            // Término original tiene máxima prioridad
            $escapedOriginal = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $scoringCases[] = "CASE WHEN negocios.nombre LIKE " . DB::getPdo()->quote($escapedOriginal) . " THEN 150 ELSE 0 END";
            $scoringCases[] = "CASE WHEN negocios.especialidad LIKE " . DB::getPdo()->quote($escapedOriginal) . " THEN 100 ELSE 0 END";
            $scoringCases[] = "CASE WHEN negocios.palabras_clave LIKE " . DB::getPdo()->quote($escapedOriginal) . " THEN 80 ELSE 0 END";
            $scoringCases[] = "CASE WHEN negocios.descripcion LIKE " . DB::getPdo()->quote($escapedOriginal) . " THEN 30 ELSE 0 END";

            // Recorrer los términos fuzzy y sinónimos expandidos
            foreach ($searchTerms as $term) {
                $escapedTerm = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                // Puntuaciones
                $scoringCases[] = "CASE WHEN negocios.nombre LIKE " . DB::getPdo()->quote($escapedTerm) . " THEN 80 ELSE 0 END";
                $scoringCases[] = "CASE WHEN negocios.especialidad LIKE " . DB::getPdo()->quote($escapedTerm) . " THEN 60 ELSE 0 END";
                $scoringCases[] = "CASE WHEN negocios.palabras_clave LIKE " . DB::getPdo()->quote($escapedTerm) . " THEN 40 ELSE 0 END";
                
                // Relación con categoría
                $scoringCases[] = "CASE WHEN EXISTS (
                    SELECT 1 FROM categorias WHERE categorias.id = negocios.categoria_id 
                    AND categorias.nombre LIKE " . DB::getPdo()->quote($escapedTerm) . "
                ) THEN 70 ELSE 0 END";

                // Relación con servicios
                $scoringCases[] = "CASE WHEN EXISTS (
                    SELECT 1 FROM servicios WHERE servicios.negocio_id = negocios.id 
                    AND servicios.nombre LIKE " . DB::getPdo()->quote($escapedTerm) . "
                ) THEN 30 ELSE 0 END";
            }

            // Suma del scoring de relevancia
            $relevanceSelect = ", (" . implode(" + ", $scoringCases) . ") as relevance_score";
            $query->selectRaw("negocios.*" . $relevanceSelect);
            
            // Filtrar para que solo devuelva resultados con alguna relevancia
            $query->where(function($sub) use ($searchTerms, $q) {
                $escapedOriginal = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $sub->where('negocios.nombre', 'like', $escapedOriginal)
                    ->orWhere('negocios.descripcion', 'like', $escapedOriginal)
                    ->orWhere('negocios.especialidad', 'like', $escapedOriginal)
                    ->orWhere('negocios.palabras_clave', 'like', $escapedOriginal)
                    ->orWhere('negocios.ciudad', 'like', $escapedOriginal)
                    ->orWhere('negocios.municipio', 'like', $escapedOriginal);

                foreach ($searchTerms as $term) {
                    $escapedTerm = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                    $sub->orWhere('negocios.nombre', 'like', $escapedTerm)
                        ->orWhere('negocios.especialidad', 'like', $escapedTerm)
                        ->orWhere('negocios.palabras_clave', 'like', $escapedTerm)
                        ->orWhereHas('categoria', fn($catQuery) => $catQuery->where('nombre', 'like', $escapedTerm))
                        ->orWhereHas('servicios', fn($srvQuery) => $srvQuery->where('nombre', 'like', $escapedTerm));
                }
            });
        } else {
            $query->select('negocios.*');
        }

        // 4. Proximidad y Ordenación
        if ($lat && $lng) {
            $latVal = (float) $lat;
            $lngVal = (float) $lng;
            // Haversine formula (km)
            $haversine = "(6371 * acos(cos(radians({$latVal})) * cos(radians(latitud)) * cos(radians(longitud) - radians({$lngVal})) + sin(radians({$latVal})) * sin(radians(latitud))))";
            $query->selectRaw("{$haversine} AS distancia");
            
            if ($filterMasCercano) {
                // Si filtra por cercanía, ordenamos estrictamente por distancia
                $query->orderBy('distancia', 'asc');
            } else {
                // Boost de proximidad para el listado general
                $query->orderByRaw("CASE WHEN {$haversine} <= 15 THEN 0 WHEN {$haversine} <= 50 THEN 1 ELSE 2 END ASC");
            }
        }

        // Prioridad Jerárquica de Ubicación (Evita grids vacíos mostrando locales primero, luego nacionales)
        if ($ciudadId) {
            $cId = (int) $ciudadId;
            $eId = (int) $estadoId;
            $pId = (int) $paisId;
            $query->orderByRaw("CASE WHEN ciudad_id = {$cId} THEN 0 WHEN estado_id = {$eId} THEN 1 WHEN pais_id = {$pId} THEN 2 ELSE 3 END ASC");
        } elseif ($estadoId) {
            $eId = (int) $estadoId;
            $pId = (int) $paisId;
            $query->orderByRaw("CASE WHEN estado_id = {$eId} THEN 0 WHEN pais_id = {$pId} THEN 1 ELSE 2 END ASC");
        } elseif ($paisId) {
            $pId = (int) $paisId;
            $query->orderByRaw("CASE WHEN pais_id = {$pId} THEN 0 ELSE 1 END ASC");
        }

        // Algoritmo de Rotación Inteligente (Weighted Random)
        // Weight formula: (Plan Weight + Destacado Boost + Verificado Boost) / log(visualizaciones + 2)
        // Enterprise = 40, Pro = 30, Basic = 20, Free = 10
        // Destacado = * 2.0, Verificado = * 1.3
        $weightSql = "(
            (CASE WHEN plan = 'enterprise' THEN 40 
                  WHEN plan = 'pro' THEN 30 
                  WHEN plan = 'basic' THEN 20 
                  ELSE 10 END) 
            * (CASE WHEN destacado = 1 THEN 2.0 ELSE 1.0 END)
            * (CASE WHEN verificado = 1 THEN 1.3 ELSE 1.0 END)
            / LOG(visualizaciones + 2)
        )";

        $seedVal = (int) $seed;
        if ($q) {
            // Si hay búsqueda, ordenamos principalmente por la puntuación de relevancia
            $query->orderBy('relevance_score', 'desc');
            // Como desempate, rotación ponderada
            $query->orderByRaw("POWER(RAND({$seedVal}), 1 / ({$weightSql})) DESC");
        } else {
            // Si no hay búsqueda, ordenamos según el filtro del usuario
            if ($filterMejorValorado) {
                $query->orderBy('resenas_avg_calificacion', 'desc');
            }
            // Rotación por defecto ponderada con el seed de sesión
            $query->orderByRaw("POWER(RAND({$seedVal}), 1 / ({$weightSql})) DESC");
        }

        // Paginación estable
        $negocios = $query->paginate(24);

        $negocios->getCollection()->transform(function ($negocio) {
            $negocio->logo = $negocio->logo ? asset('storage/' . $negocio->logo) : null;
            $negocio->cover_imagen = $negocio->cover_imagen ? asset('storage/' . $negocio->cover_imagen) : null;
            
            // Redondear promedio de reseñas
            $negocio->rating_avg = $negocio->resenas_avg_calificacion ? round($negocio->resenas_avg_calificacion, 1) : 0;
            $negocio->rating_count = $negocio->resenas_count ?? 0;
            
            $nextAvailable = now()->addDays(mt_rand(0, 5));
            $negocio->next_available_day = $nextAvailable->format('d');
            $negocio->next_available_month = $nextAvailable->translatedFormat('F'); // Ej: Julio, Agosto...
            $negocio->next_available_weekday = $nextAvailable->translatedFormat('l'); // Ej: sábado, jueves...
            
            // Asignar tamaños asimétricos alternados para el mosaico orgánico
            $sizes = ['medium', 'large', 'vertical', 'medium', 'large'];
            $negocio->layout_size = $sizes[$negocio->id % count($sizes)];
            
            return $negocio;
        });

        return response()->json([
            'success' => true,
            'data' => $negocios,
            'location_detected' => [
                'pais_id' => $paisId,
                'estado_id' => $estadoId,
                'ciudad_id' => $ciudadId,
                'lat' => $lat,
                'lng' => $lng,
                'ip_based' => !empty($ipLocation),
            ],
            'query_corrected' => ($q && strtolower($queryCorrected) !== strtolower($q)) ? $queryCorrected : null,
            'seed' => $seed,
        ]);
    }

    /**
     * GET /api/directorio/sugerencias
     * Autocompletado rápido para el buscador
     */
    public function sugerencias(Request $request): JsonResponse
    {
        $q = $request->input('q');
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $sugerencias = collect();

        // 1. Categorías coincidentes
        $categorias = Categoria::where('nombre', 'like', "%{$q}%")
            ->where('activo', true)
            ->limit(3)
            ->get();
        foreach ($categorias as $cat) {
            $sugerencias->push([
                'texto' => $cat->nombre,
                'tipo' => 'categoria',
                'icono' => $cat->icono ?? '📁',
                'id' => $cat->id
            ]);
        }

        // 2. Especialidades coincidentes
        $especialidades = Negocio::where('especialidad', 'like', "%{$q}%")
            ->where('activo', true)
            ->distinct()
            ->limit(3)
            ->pluck('especialidad');
        foreach ($especialidades as $esp) {
            if ($esp) {
                $sugerencias->push([
                    'texto' => $esp,
                    'tipo' => 'especialidad',
                    'icono' => '✨'
                ]);
            }
        }

        // 3. Negocios coincidentes
        $negocios = Negocio::where('nombre', 'like', "%{$q}%")
            ->where('activo', true)
            ->limit(4)
            ->get(['id', 'nombre', 'logo', 'slug']);
        foreach ($negocios as $neg) {
            $sugerencias->push([
                'texto' => $neg->nombre,
                'tipo' => 'negocio',
                'icono' => '🏢',
                'slug' => $neg->slug,
                'logo' => $neg->logo ? asset('storage/' . $neg->logo) : null
            ]);
        }

        // 4. Servicios sugeridos
        $servicios = DB::table('servicios')
            ->join('negocios', 'servicios.negocio_id', '=', 'negocios.id')
            ->where('negocios.activo', true)
            ->where('servicios.nombre', 'like', "%{$q}%")
            ->distinct()
            ->limit(3)
            ->pluck('servicios.nombre');
        foreach ($servicios as $srv) {
            $sugerencias->push([
                'texto' => $srv,
                'tipo' => 'servicio',
                'icono' => '🛠️'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $sugerencias->take(8)
        ]);
    }

    /**
     * POST /api/directorio/track-view/{id}
     * Incrementa el contador de visualizaciones del negocio
     */
    public function trackView(int $id): JsonResponse
    {
        $negocio = Negocio::find($id);
        if ($negocio) {
            $negocio->increment('visualizaciones');
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    /**
     * Georreferenciación por IP mediante API pública con fallback a IPs de prueba en local
     */
    private function geolocateByIp(string $ip): array
    {
        // En local o testing, simulamos una IP española para pruebas consistentes
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            $ip = '95.122.0.0'; // Elche/Valencia, España
        }

        try {
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    return [
                        'pais_codigo' => $data['countryCode'] ?? 'ES',
                        'region' => $data['regionName'] ?? null,
                        'ciudad' => $data['city'] ?? null,
                        'latitud' => $data['lat'] ?? null,
                        'longitud' => $data['lon'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback silencioso
        }

        return [];
    }

    /**
     * Corrección Fuzzy y Expansión por Sinónimos
     */
    private function processFuzzySearchAndSynonyms(string $query): array
    {
        $words = explode(' ', strtolower(trim($query)));
        $dictionary = [
            'dentista', 'odontólogo', 'peluquería', 'barbería', 'fisioterapeuta', 'clínica',
            'salud', 'veterinaria', 'estética', 'belleza', 'gimnasio', 'fitness', 'educación',
            'clases', 'spa', 'masaje', 'manicura', 'pedicura', 'psicólogo', 'nutricionista'
        ];

        $synonyms = [
            'dentista' => ['odontólogo', 'odontología', 'ortodoncia', 'implantología', 'endodoncia', 'higiene dental', 'clínica dental', 'médico dental'],
            'odontólogo' => ['dentista', 'odontología', 'ortodoncia', 'clínica dental'],
            'peluquería' => ['barbería', 'centro de belleza', 'estética', 'coloración', 'corte masculino', 'corte femenino', 'peluquero', 'barbero'],
            'barbería' => ['peluquería', 'barbero', 'corte masculino'],
            'fisioterapeuta' => ['fisioterapia', 'masaje', 'masajista', 'spa', 'relajación'],
            'veterinaria' => ['veterinario', 'veterinarios', 'mascotas', 'peluquería canina', 'adiestramiento'],
            'gimnasio' => ['fitness', 'entrenamiento personal', 'yoga', 'pilates', 'meditación'],
            'estética' => ['belleza', 'spa', 'manicura', 'pedicura', 'centro de belleza'],
        ];

        // Mapeo directo de errores de escritura comunes
        $typoCorrections = [
            'dentita' => 'dentista',
            'peluqeria' => 'peluquería',
            'barveria' => 'barbería',
            'fisoterapeuta' => 'fisioterapeuta',
            'odontologia' => 'odontólogo',
            'veterinario' => 'veterinaria',
            'estetia' => 'estética',
            'masajista' => 'fisioterapeuta',
        ];

        $correctedWords = [];
        $expandedTerms = [];

        foreach ($words as $word) {
            if (empty($word) || strlen($word) < 3) continue;

            // 1. Corrección de typos directa
            if (isset($typoCorrections[$word])) {
                $word = $typoCorrections[$word];
            } else {
                // 2. Levenshtein contra el diccionario
                $closest = null;
                $shortestDistance = -1;
                foreach ($dictionary as $dictWord) {
                    $dist = levenshtein($word, $dictWord);
                    if ($dist <= 2 && ($shortestDistance === -1 || $dist < $shortestDistance)) {
                        $closest = $dictWord;
                        $shortestDistance = $dist;
                    }
                }
                if ($closest) {
                    $word = $closest;
                }
            }

            $correctedWords[] = $word;
            $expandedTerms[] = $word;

            // 3. Expansión por sinónimos
            if (isset($synonyms[$word])) {
                $expandedTerms = array_merge($expandedTerms, $synonyms[$word]);
            }
        }

        return [
            'corrected_query' => implode(' ', $correctedWords),
            'search_terms' => array_unique($expandedTerms)
        ];
    }
}
