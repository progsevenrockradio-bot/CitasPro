<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Profesional;
use App\Models\Negocio;
use App\Models\Categoria;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@citaspro.com'],
            [
                'name' => 'Súper Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $categoria = Categoria::firstOrCreate(
            ['slug' => 'general'],
            ['nombre' => 'General', 'activo' => true]
        );

        $negocio = Negocio::firstOrCreate(
            ['slug' => 'demo'],
            [
                'nombre' => 'CitasPro Demo',
                'categoria_id' => $categoria->id,
                'activo' => true
            ]
        );

        Profesional::updateOrCreate(
            ['email' => 'admin@citaspro.com'],
            [
                'negocio_id' => $negocio->id,
                'nombre' => 'Administrador',
                'apellido' => 'Maestro',
                'telefono' => '+34600111222',
                'password' => Hash::make('admin123'),
                'rol' => 'dueño',
                'activo' => true
            ]
        );
    }
}
