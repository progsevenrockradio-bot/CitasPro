<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Cliente;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Crear una Categoría de prueba si no existe
        $categoria = \App\Models\Categoria::firstOrCreate(
            ['slug' => 'barberia'],
            ['nombre' => 'Barbería', 'descripcion' => 'Cortes y barba', 'icono' => 'scissors', 'activo' => true]
        );

        // 1. Crear un Negocio de prueba
        $negocio = Negocio::firstOrCreate(
            ['slug' => 'barberia-demo'],
            [
                'categoria_id' => $categoria->id,
                'nombre' => 'Barbería Demo',
                'telefono' => '123456789',
                'direccion' => 'Calle Falsa 123'
            ]
        );

        // 2. Crear un Profesional de prueba
        $profesional = Profesional::firstOrCreate(
            ['email' => 'demo@citaspro.com'],
            [
                'negocio_id' => $negocio->id,
                'nombre' => 'Carlos',
                'apellido' => 'Pérez',
                'telefono' => '987654321',
                'whatsapp' => '987654321',
                'activo' => true
            ]
        );

        // 3. Crear Servicios de prueba
        $servicios = [
            ['nombre' => 'Corte de Pelo', 'precio' => 15.00, 'duracion_min' => 30],
            ['nombre' => 'Arreglo de Barba', 'precio' => 10.00, 'duracion_min' => 20],
            ['nombre' => 'Corte + Barba', 'precio' => 22.00, 'duracion_min' => 45],
            ['nombre' => 'Tinte', 'precio' => 35.00, 'duracion_min' => 60],
        ];

        foreach ($servicios as $srv) {
            Servicio::firstOrCreate(
                ['negocio_id' => $negocio->id, 'nombre' => $srv['nombre']],
                [
                    'precio' => $srv['precio'],
                    'duracion_min' => $srv['duracion_min'],
                    'activo' => true
                ]
            );
        }
        
        // 4. Crear algunos clientes de prueba
        $clientes = [
            ['nombre' => 'Juan', 'apellido' => 'López', 'telefono' => '600111222'],
            ['nombre' => 'María', 'apellido' => 'García', 'telefono' => '600333444'],
            ['nombre' => 'Pedro', 'apellido' => 'Martínez', 'telefono' => '600555666'],
        ];
        
        foreach ($clientes as $cli) {
            Cliente::firstOrCreate(
                ['telefono' => $cli['telefono']],
                [
                    'nombre' => $cli['nombre'],
                    'apellido' => $cli['apellido']
                ]
            );
        }
    }
}
