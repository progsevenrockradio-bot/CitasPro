<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WebConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebConfigController extends Controller
{
    /**
     * Get all web configs.
     * Accessible publicly or by SuperAdmin.
     */
    public function index()
    {
        $configs = WebConfig::all()->keyBy('key')->map(function ($item) {
            return WebConfig::getValue($item->key);
        });

        return response()->json([
            'success' => true,
            'data' => $configs
        ]);
    }

    /**
     * Update web configs.
     * Protected by SuperAdmin middleware.
     */
    public function update(Request $request)
    {
        $updated = 0;
        
        // El request puede traer strings, booleanos (como string "true"/"false") o archivos
        foreach ($request->all() as $key => $value) {
            $config = WebConfig::where('key', $key)->first();
            
            if (!$config) {
                continue;
            }

            // Manejo de subida de imágenes
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('web-config', 'public');
                $config->value = Storage::url($path);
                $config->save();
                $updated++;
                continue;
            }

            // Manejo de booleanos o json
            if ($config->type === 'boolean') {
                $val = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                $config->value = $val;
            } elseif ($config->type === 'json') {
                // Asume que si es un array, se guarda como json
                $config->value = is_array($value) ? json_encode($value) : $value;
            } else {
                $config->value = $value;
            }

            $config->save();
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se actualizaron {$updated} configuraciones.",
            'data' => WebConfig::all()->keyBy('key')->map(function ($item) {
                return WebConfig::getValue($item->key);
            })
        ]);
    }
}
