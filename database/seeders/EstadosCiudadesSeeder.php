<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Estado;
use App\Models\Pais;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EstadosCiudadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/estados_ciudades.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('No se encontró el archivo estados_ciudades.json');
            return;
        }

        $datos = json_decode(File::get($jsonPath), true);
        
        if (!$datos) {
            $this->command->error('El archivo JSON no es válido.');
            return;
        }

        foreach ($datos as $iso2 => $estados) {
            $pais = Pais::where('codigo_iso2', $iso2)->first();
            
            if (!$pais) {
                $this->command->warn("El país con ISO2 {$iso2} no existe en la tabla paises. Omitiendo...");
                continue;
            }

            foreach ($estados as $estData) {
                $estado = Estado::firstOrCreate([
                    'pais_id' => $pais->id,
                    'nombre'  => $estData['nombre'],
                ], [
                    'codigo'  => $estData['codigo'] ?? null,
                ]);

                if (isset($estData['ciudades']) && is_array($estData['ciudades'])) {
                    foreach ($estData['ciudades'] as $index => $nombreCiudad) {
                        Ciudad::firstOrCreate([
                            'estado_id' => $estado->id,
                            'nombre'    => $nombreCiudad,
                        ], [
                            // Asumimos que la primera ciudad listada suele ser la principal
                            'es_capital' => ($index === 0) ? true : false,
                        ]);
                    }
                }
            }
            
            $this->command->info("Estados y ciudades cargados para: {$pais->nombre}");
        }
    }
}
