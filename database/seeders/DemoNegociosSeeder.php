<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Categoria;
use App\Models\Pais;
use App\Models\Estado;
use App\Models\Ciudad;
use App\Models\Resena;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoNegociosSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // Asegurar que existan las categorías básicas
        $categoriasData = [
            ['slug' => 'salud-medicina', 'nombre' => 'Salud y Medicina', 'descripcion' => 'Consultas médicas y terapias', 'icono' => '🩺', 'color_hex' => '#14B8A6'],
            ['slug' => 'clinica-dental', 'nombre' => 'Clínica Dental', 'descripcion' => 'Odontología y estética dental', 'icono' => '🦷', 'color_hex' => '#06B6D4'],
            ['slug' => 'spa-estetica', 'nombre' => 'Spa & Estética', 'descripcion' => 'Relajación y masajes', 'icono' => '✨', 'color_hex' => '#EC4899'],
            ['slug' => 'peluqueria-barberia', 'nombre' => 'Peluquería y Barbería', 'descripcion' => 'Cortes de cabello y barba', 'icono' => '✂️', 'color_hex' => '#8B5CF6'],
            ['slug' => 'fitness-bienestar', 'nombre' => 'Fitness y Bienestar', 'descripcion' => 'Entrenamiento y yoga', 'icono' => '🏋️', 'color_hex' => '#10B981'],
            ['slug' => 'veterinaria-mascotas', 'nombre' => 'Veterinaria y Mascotas', 'descripcion' => 'Cuidado animal', 'icono' => '🐾', 'color_hex' => '#6366F1'],
            ['slug' => 'consultoria-asesoria', 'nombre' => 'Consultoría y Asesoría', 'descripcion' => 'Asesoramiento legal y financiero', 'icono' => '💼', 'color_hex' => '#0EA5E9'],
            ['slug' => 'educacion-clases', 'nombre' => 'Educación y Clases', 'descripcion' => 'Formación y clases particulares', 'icono' => '📚', 'color_hex' => '#F59E0B'],
        ];

        $categorias = [];
        foreach ($categoriasData as $c) {
            $categorias[$c['slug']] = Categoria::firstOrCreate(
                ['slug' => $c['slug']],
                ['nombre' => $c['nombre'], 'descripcion' => $c['descripcion'], 'icono' => $c['icono'], 'color_hex' => $c['color_hex'], 'activo' => true]
            );
        }

        // Obtener País de España
        $pais = Pais::where('codigo_iso2', 'ES')->first() ?? Pais::firstOrCreate(
            ['codigo_iso2' => 'ES'],
            [
                'nombre' => 'España',
                'nombre_en' => 'Spain',
                'codigo_iso3' => 'ESP',
                'prefijo' => '34',
                'activo' => true
            ]
        );

        // Crear Estados y Ciudades mock si no existen
        $estadoComunidadValenciana = Estado::firstOrCreate(['pais_id' => $pais->id, 'nombre' => 'Comunidad Valenciana'], ['codigo' => 'VC']);
        $estadoMadrid = Estado::firstOrCreate(['pais_id' => $pais->id, 'nombre' => 'Comunidad de Madrid'], ['codigo' => 'MD']);
        $estadoCatalunya = Estado::firstOrCreate(['pais_id' => $pais->id, 'nombre' => 'Cataluña'], ['codigo' => 'CT']);

        $ciudadElche = Ciudad::firstOrCreate(['estado_id' => $estadoComunidadValenciana->id, 'nombre' => 'Elche']);
        $ciudadAlicante = Ciudad::firstOrCreate(['estado_id' => $estadoComunidadValenciana->id, 'nombre' => 'Alicante']);
        $ciudadValencia = Ciudad::firstOrCreate(['estado_id' => $estadoComunidadValenciana->id, 'nombre' => 'Valencia']);
        $ciudadMadrid = Ciudad::firstOrCreate(['estado_id' => $estadoMadrid->id, 'nombre' => 'Madrid']);
        $ciudadBarcelona = Ciudad::firstOrCreate(['estado_id' => $estadoCatalunya->id, 'nombre' => 'Barcelona']);

        // Crear Clientes Mock para reseñas
        $clientes = [];
        $clientesData = [
            ['nombre' => 'Sofía', 'apellido' => 'García', 'telefono' => '+34600123456'],
            ['nombre' => 'Mateo', 'apellido' => 'Fernández', 'telefono' => '+34600654321'],
            ['nombre' => 'Martina', 'apellido' => 'Sánchez', 'telefono' => '+34600987654'],
            ['nombre' => 'Lucas', 'apellido' => 'Gómez', 'telefono' => '+34600112233'],
        ];
        foreach ($clientesData as $cd) {
            $clientes[] = Cliente::firstOrCreate(['telefono' => $cd['telefono']], ['nombre' => $cd['nombre'], 'apellido' => $cd['apellido']]);
        }

        // Definición de Negocios
        $negocios = [
            [
                'slug' => 'clinica-san-jose',
                'nombre' => 'Clínica Médica San José',
                'categoria_slug' => 'salud-medicina',
                'especialidad' => 'Pediatría y Medicina General',
                'palabras_clave' => 'médico, pediatra, consulta médica, salud, bienestar, clínico, elche',
                'direccion' => 'Calle Reina Victoria 45',
                'ciudad' => 'Elche',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadElche->id,
                'latitud' => 38.262200,
                'longitud' => -0.699600,
                'plan' => 'enterprise',
                'verificado' => true,
                'destacado' => true,
                'layout_size' => 'large',
                'tipo_clinica' => 'medical',
                'visualizaciones' => 120,
                'descripcion' => 'Clínica familiar en Elche con especialistas de alto nivel y tecnología de vanguardia.'
            ],
            [
                'slug' => 'sonrisa-perfecta',
                'nombre' => 'Clínica Dental Sonrisa Perfecta',
                'categoria_slug' => 'clinica-dental',
                'especialidad' => 'Ortodoncia e Implantes Dentales',
                'palabras_clave' => 'dentista, caries, ortodoncia, odontología, brackets, limpieza dental, implantes',
                'direccion' => 'Av. de Alfonso El Sabio 12',
                'ciudad' => 'Alicante',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadAlicante->id,
                'latitud' => 38.345200,
                'longitud' => -0.481000,
                'plan' => 'pro',
                'verificado' => true,
                'destacado' => false,
                'layout_size' => 'vertical',
                'tipo_clinica' => 'dental',
                'visualizaciones' => 85,
                'descripcion' => 'Diseño de sonrisas, implantes de titanio y ortodoncia invisible con los mejores odontólogos.'
            ],
            [
                'slug' => 'zen-spa-relax',
                'nombre' => 'Zen Spa & Wellness',
                'categoria_slug' => 'spa-estetica',
                'especialidad' => 'Masajes Relajantes y Tratamientos Faciales',
                'palabras_clave' => 'spa, masajes, relajación, masajista, exfoliación, estética, belleza, valencia',
                'direccion' => 'Calle Colón 23',
                'ciudad' => 'Valencia',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadValencia->id,
                'latitud' => 39.469900,
                'longitud' => -0.376300,
                'plan' => 'basic',
                'verificado' => false,
                'destacado' => false,
                'layout_size' => 'medium',
                'tipo_clinica' => null,
                'visualizaciones' => 45,
                'descripcion' => 'Un oasis de paz en el centro de Valencia. Disfruta de masajes relajantes y aromaterapia.'
            ],
            [
                'slug' => 'peluqueria-velvet-elche',
                'nombre' => 'Velvet Peluquería y Estilismo',
                'categoria_slug' => 'peluqueria-barberia',
                'especialidad' => 'Coloración y Cortes de Diseño',
                'palabras_clave' => 'peluquería, tinte, mechas, corte de pelo, estilista, peinado, elche',
                'direccion' => 'Av. de Novelda 78',
                'ciudad' => 'Elche',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadElche->id,
                'latitud' => 38.266200,
                'longitud' => -0.698000,
                'plan' => 'pro',
                'verificado' => true,
                'destacado' => true,
                'layout_size' => 'horizontal',
                'tipo_clinica' => null,
                'visualizaciones' => 200,
                'descripcion' => 'Expertas en balayage, tratamientos de queratina y cortes modernos adaptados a tu estilo.'
            ],
            [
                'slug' => 'barberia-el-bigote-elche',
                'nombre' => 'Barbería El Bigote de Oro',
                'categoria_slug' => 'peluqueria-barberia',
                'especialidad' => 'Corte de Cabello y Barboterapia',
                'palabras_clave' => 'barbería, barbero, corte de pelo hombre, barba, afeitado clásico, elche',
                'direccion' => 'Calle Vicente Blasco Ibáñez 32',
                'ciudad' => 'Elche',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadElche->id,
                'latitud' => 38.261000,
                'longitud' => -0.702000,
                'plan' => 'free',
                'verificado' => false,
                'destacado' => false,
                'layout_size' => 'small',
                'tipo_clinica' => null,
                'visualizaciones' => 110,
                'descripcion' => 'Barbería vintage para hombres y niños en Elche. Afeitados tradicionales con navaja.'
            ],
            [
                'slug' => 'fitness-energy-madrid',
                'nombre' => 'Energy Fitness Center',
                'categoria_slug' => 'fitness-bienestar',
                'especialidad' => 'Entrenamiento Personal y Pilates',
                'palabras_clave' => 'gimnasio, fitness, pilates, entrenador personal, yoga, musculación, madrid',
                'direccion' => 'Paseo de la Castellana 112',
                'ciudad' => 'Madrid',
                'estado_id' => $estadoMadrid->id,
                'ciudad_id' => $ciudadMadrid->id,
                'latitud' => 40.416800,
                'longitud' => -3.703800,
                'plan' => 'enterprise',
                'verificado' => true,
                'destacado' => true,
                'layout_size' => 'featured',
                'tipo_clinica' => null,
                'visualizaciones' => 320,
                'descripcion' => 'Entrenamientos adaptados de alta intensidad, área cardiovascular y clases guiadas en Madrid.'
            ],
            [
                'slug' => 'veterinaria-mascotas-valencia',
                'nombre' => 'Clínica Veterinaria Mascotas Felices',
                'categoria_slug' => 'veterinaria-mascotas',
                'especialidad' => 'Cirugía General y Urgencias 24h',
                'palabras_clave' => 'veterinaria, veterinario, vacunas perro, gato, urgencias mascotas, peluquería canina',
                'direccion' => 'Calle de la Paz 8',
                'ciudad' => 'Valencia',
                'estado_id' => $estadoComunidadValenciana->id,
                'ciudad_id' => $ciudadValencia->id,
                'latitud' => 39.462000,
                'longitud' => -0.392000,
                'plan' => 'basic',
                'verificado' => true,
                'destacado' => false,
                'layout_size' => 'medium',
                'tipo_clinica' => null,
                'visualizaciones' => 90,
                'descripcion' => 'Servicios médicos avanzados, radiografía digital y peluquería para tus mejores amigos.'
            ],
            [
                'slug' => 'asesoria-fiscal-pro',
                'nombre' => 'Asesoría Fiscal Pro & Asociados',
                'categoria_slug' => 'consultoria-asesoria',
                'especialidad' => 'Planificación Tributaria y Contable',
                'palabras_clave' => 'asesoría, consultoría, impuestos, autónomos, empresas, contabilidad, madrid',
                'direccion' => 'Calle Gran Vía 15',
                'ciudad' => 'Madrid',
                'estado_id' => $estadoMadrid->id,
                'ciudad_id' => $ciudadMadrid->id,
                'latitud' => 40.420000,
                'longitud' => -3.680000,
                'plan' => 'free',
                'verificado' => false,
                'destacado' => false,
                'layout_size' => 'horizontal',
                'tipo_clinica' => null,
                'visualizaciones' => 60,
                'descripcion' => 'Llevamos la contabilidad e impuestos de tu negocio. Asesoramiento laboral y legal premium.'
            ],
            [
                'slug' => 'academia-brainy-barcelona',
                'nombre' => 'Brainy Academy & Idiomas',
                'categoria_slug' => 'educacion-clases',
                'especialidad' => 'Refuerzo Escolar e Inglés',
                'palabras_clave' => 'clases particulares, inglés, refuerzo escolar, matemáticas, academia, barcelona',
                'direccion' => 'Rambla de Catalunya 54',
                'ciudad' => 'Barcelona',
                'estado_id' => $estadoCatalunya->id,
                'ciudad_id' => $ciudadBarcelona->id,
                'latitud' => 41.385100,
                'longitud' => 2.173400,
                'plan' => 'pro',
                'verificado' => true,
                'destacado' => false,
                'layout_size' => 'vertical',
                'tipo_clinica' => null,
                'visualizaciones' => 140,
                'descripcion' => 'Apoyo escolar para primaria y secundaria, y preparación de exámenes oficiales Cambridge.'
            ]
        ];

        foreach ($negocios as $nData) {
            $catSlug = $nData['categoria_slug'];
            unset($nData['categoria_slug']);

            $nData['categoria_id'] = $categorias[$catSlug]->id;
            $nData['pais'] = 'ES';
            $nData['pais_id'] = $pais->id;
            $nData['telefono'] = '+34600' . mt_rand(100000, 999999);
            $nData['whatsapp'] = $nData['telefono'];
            $nData['booking_activo'] = true;
            $nData['activo'] = true;

            $negocio = Negocio::updateOrCreate(['slug' => $nData['slug']], $nData);

            // Crear profesional dueño
            $profEmail = 'info@' . $negocio->slug . '.com';
            $prof = Profesional::firstOrCreate(
                ['email' => $profEmail],
                [
                    'negocio_id' => $negocio->id,
                    'nombre' => 'Director',
                    'apellido' => $negocio->nombre,
                    'telefono' => $negocio->telefono,
                    'password' => $password,
                    'rol' => 'dueño',
                    'activo' => true,
                    'aceptar_online' => true,
                    'type' => $negocio->tipo_clinica ?? 'general'
                ]
            );

            // Crear servicio base
            $servicio = Servicio::firstOrCreate(
                ['negocio_id' => $negocio->id, 'nombre' => 'Servicio General / Consulta'],
                [
                    'precio' => mt_rand(30, 80),
                    'duracion_min' => mt_rand(30, 60),
                    'activo' => true
                ]
            );
            $prof->servicios()->syncWithoutDetaching([$servicio->id]);

            // Agregar Reseñas Mock
            Resena::where('negocio_id', $negocio->id)->delete();
            foreach ($clientes as $c) {
                Resena::create([
                    'negocio_id' => $negocio->id,
                    'profesional_id' => $prof->id,
                    'cliente_id' => $c->id,
                    'calificacion' => mt_rand(4, 5),
                    'comentario' => 'Excelente servicio y trato muy profesional. Totalmente recomendado.',
                    'activo' => true
                ]);
            }
        }

        $this->command->info('Base de datos del directorio poblada con 9 negocios estructurados de prueba.');
    }
}
