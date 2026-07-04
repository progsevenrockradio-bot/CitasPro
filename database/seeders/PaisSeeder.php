<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaisSeeder extends Seeder
{
    public function run(): void
    {
        $paises = [
            // Europa
            ['nombre' => 'España', 'nombre_en' => 'Spain', 'codigo_iso2' => 'ES', 'codigo_iso3' => 'ESP', 'prefijo' => '34', 'bandera' => '🇪🇸', 'region' => 'europa', 'orden_preferencia' => 1],
            ['nombre' => 'Portugal', 'nombre_en' => 'Portugal', 'codigo_iso2' => 'PT', 'codigo_iso3' => 'PRT', 'prefijo' => '351', 'bandera' => '🇵🇹', 'region' => 'europa', 'orden_preferencia' => 10],
            ['nombre' => 'Francia', 'nombre_en' => 'France', 'codigo_iso2' => 'FR', 'codigo_iso3' => 'FRA', 'prefijo' => '33', 'bandera' => '🇫🇷', 'region' => 'europa', 'orden_preferencia' => 20],
            ['nombre' => 'Italia', 'nombre_en' => 'Italy', 'codigo_iso2' => 'IT', 'codigo_iso3' => 'ITA', 'prefijo' => '39', 'bandera' => '🇮🇹', 'region' => 'europa', 'orden_preferencia' => 20],
            ['nombre' => 'Reino Unido', 'nombre_en' => 'United Kingdom', 'codigo_iso2' => 'GB', 'codigo_iso3' => 'GBR', 'prefijo' => '44', 'bandera' => '🇬🇧', 'region' => 'europa', 'orden_preferencia' => 20],
            ['nombre' => 'Alemania', 'nombre_en' => 'Germany', 'codigo_iso2' => 'DE', 'codigo_iso3' => 'DEU', 'prefijo' => '49', 'bandera' => '🇩🇪', 'region' => 'europa', 'orden_preferencia' => 20],

            // América del Norte
            ['nombre' => 'Estados Unidos', 'nombre_en' => 'United States', 'codigo_iso2' => 'US', 'codigo_iso3' => 'USA', 'prefijo' => '1', 'bandera' => '🇺🇸', 'region' => 'america_norte', 'orden_preferencia' => 2],
            ['nombre' => 'Canadá', 'nombre_en' => 'Canada', 'codigo_iso2' => 'CA', 'codigo_iso3' => 'CAN', 'prefijo' => '1', 'bandera' => '🇨🇦', 'region' => 'america_norte', 'orden_preferencia' => 15],
            ['nombre' => 'México', 'nombre_en' => 'Mexico', 'codigo_iso2' => 'MX', 'codigo_iso3' => 'MEX', 'prefijo' => '52', 'bandera' => '🇲🇽', 'region' => 'america_norte', 'orden_preferencia' => 5],

            // América del Sur
            ['nombre' => 'Colombia', 'nombre_en' => 'Colombia', 'codigo_iso2' => 'CO', 'codigo_iso3' => 'COL', 'prefijo' => '57', 'bandera' => '🇨🇴', 'region' => 'america_sur', 'orden_preferencia' => 3],
            ['nombre' => 'Venezuela', 'nombre_en' => 'Venezuela', 'codigo_iso2' => 'VE', 'codigo_iso3' => 'VEN', 'prefijo' => '58', 'bandera' => '🇻🇪', 'region' => 'america_sur', 'orden_preferencia' => 4],
            ['nombre' => 'Ecuador', 'nombre_en' => 'Ecuador', 'codigo_iso2' => 'EC', 'codigo_iso3' => 'ECU', 'prefijo' => '593', 'bandera' => '🇪🇨', 'region' => 'america_sur', 'orden_preferencia' => 6],
            ['nombre' => 'Perú', 'nombre_en' => 'Peru', 'codigo_iso2' => 'PE', 'codigo_iso3' => 'PER', 'prefijo' => '51', 'bandera' => '🇵🇪', 'region' => 'america_sur', 'orden_preferencia' => 6],
            ['nombre' => 'Chile', 'nombre_en' => 'Chile', 'codigo_iso2' => 'CL', 'codigo_iso3' => 'CHL', 'prefijo' => '56', 'bandera' => '🇨🇱', 'region' => 'america_sur', 'orden_preferencia' => 6],
            ['nombre' => 'Argentina', 'nombre_en' => 'Argentina', 'codigo_iso2' => 'AR', 'codigo_iso3' => 'ARG', 'prefijo' => '54', 'bandera' => '🇦🇷', 'region' => 'america_sur', 'orden_preferencia' => 6],
            ['nombre' => 'Brasil', 'nombre_en' => 'Brazil', 'codigo_iso2' => 'BR', 'codigo_iso3' => 'BRA', 'prefijo' => '55', 'bandera' => '🇧🇷', 'region' => 'america_sur', 'orden_preferencia' => 12],
            ['nombre' => 'Uruguay', 'nombre_en' => 'Uruguay', 'codigo_iso2' => 'UY', 'codigo_iso3' => 'URY', 'prefijo' => '598', 'bandera' => '🇺🇾', 'region' => 'america_sur', 'orden_preferencia' => 15],
            ['nombre' => 'Paraguay', 'nombre_en' => 'Paraguay', 'codigo_iso2' => 'PY', 'codigo_iso3' => 'PRY', 'prefijo' => '595', 'bandera' => '🇵🇾', 'region' => 'america_sur', 'orden_preferencia' => 15],
            ['nombre' => 'Bolivia', 'nombre_en' => 'Bolivia', 'codigo_iso2' => 'BO', 'codigo_iso3' => 'BOL', 'prefijo' => '591', 'bandera' => '🇧🇴', 'region' => 'america_sur', 'orden_preferencia' => 15],

            // América Central y Caribe
            ['nombre' => 'Panamá', 'nombre_en' => 'Panama', 'codigo_iso2' => 'PA', 'codigo_iso3' => 'PAN', 'prefijo' => '507', 'bandera' => '🇵🇦', 'region' => 'america_central', 'orden_preferencia' => 8],
            ['nombre' => 'Costa Rica', 'nombre_en' => 'Costa Rica', 'codigo_iso2' => 'CR', 'codigo_iso3' => 'CRI', 'prefijo' => '506', 'bandera' => '🇨🇷', 'region' => 'america_central', 'orden_preferencia' => 10],
            ['nombre' => 'República Dominicana', 'nombre_en' => 'Dominican Republic', 'codigo_iso2' => 'DO', 'codigo_iso3' => 'DOM', 'prefijo' => '1', 'bandera' => '🇩🇴', 'region' => 'caribe', 'orden_preferencia' => 10],
            ['nombre' => 'Puerto Rico', 'nombre_en' => 'Puerto Rico', 'codigo_iso2' => 'PR', 'codigo_iso3' => 'PRI', 'prefijo' => '1', 'bandera' => '🇵🇷', 'region' => 'caribe', 'orden_preferencia' => 10],
            ['nombre' => 'Guatemala', 'nombre_en' => 'Guatemala', 'codigo_iso2' => 'GT', 'codigo_iso3' => 'GTM', 'prefijo' => '502', 'bandera' => '🇬🇹', 'region' => 'america_central', 'orden_preferencia' => 15],
            ['nombre' => 'El Salvador', 'nombre_en' => 'El Salvador', 'codigo_iso2' => 'SV', 'codigo_iso3' => 'SLV', 'prefijo' => '503', 'bandera' => '🇸🇻', 'region' => 'america_central', 'orden_preferencia' => 15],
            ['nombre' => 'Honduras', 'nombre_en' => 'Honduras', 'codigo_iso2' => 'HN', 'codigo_iso3' => 'HND', 'prefijo' => '504', 'bandera' => '🇭🇳', 'region' => 'america_central', 'orden_preferencia' => 15],
            ['nombre' => 'Nicaragua', 'nombre_en' => 'Nicaragua', 'codigo_iso2' => 'NI', 'codigo_iso3' => 'NIC', 'prefijo' => '505', 'bandera' => '🇳🇮', 'region' => 'america_central', 'orden_preferencia' => 15],
        ];

        foreach ($paises as $pais) {
            DB::table('paises')->updateOrInsert(
                ['codigo_iso2' => $pais['codigo_iso2']],
                $pais
            );
        }
    }
}
