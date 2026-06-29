<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    /**
     * Categorías de sectores de negocio para CitasPro.
     * Multi-rubro: cubre los principales mercados objetivo.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre'      => 'Peluquería y Barbería',
                'slug'        => 'peluqueria-barberia',
                'descripcion' => 'Cortes de cabello, coloración, barba y tratamientos capilares.',
                'icono'       => '✂️',
                'color_hex'   => '#8B5CF6',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Estética y Belleza',
                'slug'        => 'estetica-belleza',
                'descripcion' => 'Manicura, pedicura, depilación, tratamientos faciales y corporales.',
                'icono'       => '💅',
                'color_hex'   => '#EC4899',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Salud y Medicina',
                'slug'        => 'salud-medicina',
                'descripcion' => 'Consultas médicas, fisioterapia, psicología y especialidades médicas.',
                'icono'       => '🩺',
                'color_hex'   => '#14B8A6',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Educación y Clases',
                'slug'        => 'educacion-clases',
                'descripcion' => 'Clases particulares, idiomas, música, deporte y formación profesional.',
                'icono'       => '📚',
                'color_hex'   => '#F59E0B',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Fitness y Bienestar',
                'slug'        => 'fitness-bienestar',
                'descripcion' => 'Gimnasios, yoga, pilates, entrenamiento personal y meditación.',
                'icono'       => '🏋️',
                'color_hex'   => '#10B981',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Veterinaria y Mascotas',
                'slug'        => 'veterinaria-mascotas',
                'descripcion' => 'Consultas veterinarias, peluquería canina, adiestramiento.',
                'icono'       => '🐾',
                'color_hex'   => '#6366F1',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Consultoría y Asesoría',
                'slug'        => 'consultoria-asesoria',
                'descripcion' => 'Asesoría legal, financiera, empresarial y coaching.',
                'icono'       => '💼',
                'color_hex'   => '#0EA5E9',
                'activo'      => true,
            ],
            [
                'nombre'      => 'Otros Servicios',
                'slug'        => 'otros-servicios',
                'descripcion' => 'Cualquier tipo de negocio que requiera gestión de citas.',
                'icono'       => '🔧',
                'color_hex'   => '#6B7280',
                'activo'      => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['slug' => $categoria['slug']],
                $categoria
            );
        }

        $this->command->info('✅ Categorías sembradas correctamente (' . count($categorias) . ' registros).');
    }
}
