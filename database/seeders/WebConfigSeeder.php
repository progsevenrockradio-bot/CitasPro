<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // Fonts
            ['key' => 'font_primary', 'value' => 'Outfit', 'type' => 'font'],
            ['key' => 'font_h1', 'value' => 'Outfit', 'type' => 'font'],
            ['key' => 'font_h2', 'value' => 'Outfit', 'type' => 'font'],
            
            // Textos Hero
            ['key' => 'hero_badge', 'value' => 'SaaS AUTOMATIZADO DE RESERVAS', 'type' => 'string'],
            ['key' => 'hero_title', 'value' => 'Directorio de Negocios', 'type' => 'string'],
            ['key' => 'hero_subtitle', 'value' => 'Encuentra y reserva al instante con los mejores profesionales cerca de ti.', 'type' => 'text'],
            
            // Imágenes
            ['key' => 'logo_url', 'value' => '/images/logo.png', 'type' => 'image'],
            ['key' => 'hero_bg_url', 'value' => '/images/bg-directorio.png', 'type' => 'image'],
            
            // Colores de la Paleta
            ['key' => 'color_bg', 'value' => '#0B1021', 'type' => 'color'],
            ['key' => 'color_bg_card', 'value' => '#171C36', 'type' => 'color'],
            ['key' => 'color_primary', 'value' => '#3B82F6', 'type' => 'color'],
            ['key' => 'color_accent', 'value' => '#FF5A5F', 'type' => 'color'],
            ['key' => 'color_border', 'value' => '#1E293B', 'type' => 'color'],
            
            // Comportamiento Tarjetas Pricing
            ['key' => 'pricing_show_free', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'pricing_pro_highlight', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'pricing_enterprise_contact', 'value' => '/contacto', 'type' => 'string'],
        ];

        foreach ($configs as $config) {
            \App\Models\WebConfig::firstOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value'], 'type' => $config['type']]
            );
        }
    }
}
