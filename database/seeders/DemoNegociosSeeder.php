<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Categoria;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoNegociosSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // ==========================================
        // 1. NEGOCIO CLÍNICO
        // ==========================================
        $catClinica = Categoria::firstOrCreate(
            ['slug' => 'clinica-medica'],
            ['nombre' => 'Clínica Médica', 'descripcion' => 'Especialidades médicas', 'icono' => '🩺', 'activo' => true]
        );

        $negocioClinico = Negocio::firstOrCreate(
            ['slug' => 'clinica-san-jose'],
            [
                'categoria_id' => $catClinica->id,
                'nombre' => 'Clínica San José',
                'telefono' => '111222333',
                'direccion' => 'Av. de la Salud 123',
                'ciudad' => 'Madrid',
                'pais' => 'ES',
                'es_medico' => true,
                'tipo_clinica' => 'medical',
                'booking_activo' => true,
                'activo' => true,
                'descripcion' => 'Centro médico de especialidades con atención personalizada.'
            ]
        );

        $profClinico = Profesional::firstOrCreate(
            ['email' => 'dr.smith@clinicasanjose.com'],
            [
                'negocio_id' => $negocioClinico->id,
                'nombre' => 'John',
                'apellido' => 'Smith',
                'telefono' => '111222333',
                'password' => $password,
                'rol' => 'dueño',
                'activo' => true,
                'aceptar_online' => true,
                'type' => 'medical'
            ]
        );

        $servClinico = Servicio::firstOrCreate(
            ['negocio_id' => $negocioClinico->id, 'nombre' => 'Consulta General'],
            ['precio' => 60.00, 'duracion_min' => 30, 'activo' => true]
        );
        
        $profClinico->servicios()->syncWithoutDetaching([$servClinico->id]);


        // ==========================================
        // 2. NEGOCIO DENTAL
        // ==========================================
        $catDental = Categoria::firstOrCreate(
            ['slug' => 'clinica-dental'],
            ['nombre' => 'Clínica Dental', 'descripcion' => 'Odontología y estética', 'icono' => '🦷', 'activo' => true]
        );

        $negocioDental = Negocio::firstOrCreate(
            ['slug' => 'sonrisa-perfecta'],
            [
                'categoria_id' => $catDental->id,
                'nombre' => 'Sonrisa Perfecta',
                'telefono' => '444555666',
                'direccion' => 'Calle Molar 45',
                'ciudad' => 'Barcelona',
                'pais' => 'ES',
                'es_medico' => true,
                'tipo_clinica' => 'dental',
                'booking_activo' => true,
                'activo' => true,
                'descripcion' => 'Tu clínica dental de confianza. Especialistas en implantes y ortodoncia.'
            ]
        );

        $profDental = Profesional::firstOrCreate(
            ['email' => 'dra.lopez@sonrisaperfecta.com'],
            [
                'negocio_id' => $negocioDental->id,
                'nombre' => 'Ana',
                'apellido' => 'López',
                'telefono' => '444555666',
                'password' => $password,
                'rol' => 'dueño',
                'activo' => true,
                'aceptar_online' => true,
                'type' => 'dental'
            ]
        );

        $servDental = Servicio::firstOrCreate(
            ['negocio_id' => $negocioDental->id, 'nombre' => 'Limpieza Dental'],
            ['precio' => 45.00, 'duracion_min' => 45, 'activo' => true]
        );
        
        $profDental->servicios()->syncWithoutDetaching([$servDental->id]);


        // ==========================================
        // 3. NEGOCIO GENÉRICO (SPA / ESTÉTICA)
        // ==========================================
        $catSpa = Categoria::firstOrCreate(
            ['slug' => 'spa-estetica'],
            ['nombre' => 'Spa & Estética', 'descripcion' => 'Relajación y belleza', 'icono' => '✨', 'activo' => true]
        );

        $negocioSpa = Negocio::firstOrCreate(
            ['slug' => 'zen-spa'],
            [
                'categoria_id' => $catSpa->id,
                'nombre' => 'Zen Spa & Relax',
                'telefono' => '777888999',
                'direccion' => 'Paseo del Mar 90',
                'ciudad' => 'Valencia',
                'pais' => 'ES',
                'es_medico' => false,
                'booking_activo' => true,
                'activo' => true,
                'descripcion' => 'Desconecta de la rutina. Masajes relajantes y tratamientos faciales.'
            ]
        );

        $profSpa = Profesional::firstOrCreate(
            ['email' => 'laura@zenspa.com'],
            [
                'negocio_id' => $negocioSpa->id,
                'nombre' => 'Laura',
                'apellido' => 'Martínez',
                'telefono' => '777888999',
                'password' => $password,
                'rol' => 'dueño',
                'activo' => true,
                'aceptar_online' => true,
                'type' => 'general'
            ]
        );

        $servSpa = Servicio::firstOrCreate(
            ['negocio_id' => $negocioSpa->id, 'nombre' => 'Masaje Relajante'],
            ['precio' => 50.00, 'duracion_min' => 60, 'activo' => true]
        );
        
        $profSpa->servicios()->syncWithoutDetaching([$servSpa->id]);
        
        $this->command->info('3 negocios demo creados exitosamente (Clínico, Dental, Genérico).');
        $this->command->info('Emails para login: dr.smith@..., dra.lopez@..., laura@... | Password: password123');
    }
}
