<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Negocio;
use App\Models\Categoria;
use App\Models\Pais;
use App\Models\Estado;
use App\Models\Ciudad;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DirectorioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear Datos Base
        $categoria = Categoria::create([
            'nombre' => 'Peluquería',
            'slug' => 'peluqueria-barberia',
            'icono' => '✂️',
            'activo' => true
        ]);

        $pais = Pais::create([
            'nombre' => 'España',
            'nombre_en' => 'Spain',
            'codigo_iso2' => 'ES',
            'codigo_iso3' => 'ESP',
            'prefijo' => '34',
            'activo' => true
        ]);

        $estado = Estado::create([
            'pais_id' => $pais->id,
            'nombre' => 'Comunidad Valenciana',
            'codigo' => 'VC'
        ]);

        $ciudad = Ciudad::create([
            'estado_id' => $estado->id,
            'nombre' => 'Elche'
        ]);

        // Crear negocios de prueba
        Negocio::create([
            'nombre' => 'Peluquería Velvet',
            'slug' => 'peluqueria-velvet',
            'categoria_id' => $categoria->id,
            'pais_id' => $pais->id,
            'estado_id' => $estado->id,
            'ciudad_id' => $ciudad->id,
            'ciudad' => 'Elche',
            'pais' => 'ES',
            'latitud' => 38.2622,
            'longitud' => -0.6996,
            'especialidad' => 'Coloración y Cortes',
            'palabras_clave' => 'peluquería, tinte, estilista',
            'booking_activo' => true,
            'activo' => true
        ]);

        Negocio::create([
            'nombre' => 'Clínica Dental Sonrisa',
            'slug' => 'dental-sonrisa',
            'categoria_id' => Categoria::create(['nombre' => 'Dental', 'slug' => 'clinica-dental', 'activo' => true])->id,
            'pais_id' => $pais->id,
            'estado_id' => $estado->id,
            'ciudad_id' => $ciudad->id,
            'ciudad' => 'Elche',
            'pais' => 'ES',
            'latitud' => 38.2610,
            'longitud' => -0.7010,
            'especialidad' => 'Implantes y Brackets',
            'palabras_clave' => 'dentista, odontología, caries',
            'booking_activo' => true,
            'activo' => true
        ]);
    }

    /** @test */
    public function it_lists_active_businesses()
    {
        $response = $this->getJson('/api/directorio');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data.data'));
    }

    /** @test */
    public function it_filters_by_category()
    {
        $cat = Categoria::where('slug', 'peluqueria-barberia')->first();
        $response = $this->getJson('/api/directorio?categoria_id=' . $cat->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $response->assertJsonPath('data.data.0.slug', 'peluqueria-velvet');
    }

    /** @test */
    public function it_does_fuzzy_search_and_typo_correction()
    {
        // "peluqeria" debe corregirse fuzzy a "peluquería" y coincidir con Peluquería Velvet
        $response = $this->getJson('/api/directorio?q=peluqeria');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $response->assertJsonPath('data.data.0.slug', 'peluqueria-velvet');
        $response->assertJsonPath('query_corrected', 'peluquería');
    }

    /** @test */
    public function it_returns_search_suggestions()
    {
        $response = $this->getJson('/api/directorio/sugerencias?q=pelu');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data'));
    }
}
