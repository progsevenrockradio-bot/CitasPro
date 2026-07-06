<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaisFiscalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            'ES' => [
                'name' => 'España',
                'fields' => [
                    [
                        'key' => 'nif_cif',
                        'label' => 'NIF / CIF',
                        'type' => 'text',
                        'placeholder' => 'Ej: B12345678',
                        'required' => true,
                        'regex' => '^[A-Z0-9]{9}$',
                        'error_message' => 'El NIF/CIF debe tener 9 caracteres (letras o números).',
                    ],
                    [
                        'key' => 'razon_social',
                        'label' => 'Razón Social',
                        'type' => 'text',
                        'placeholder' => 'Nombre legal de la empresa',
                        'required' => true,
                    ]
                ]
            ],
            'MX' => [
                'name' => 'México',
                'fields' => [
                    [
                        'key' => 'rfc',
                        'label' => 'RFC',
                        'type' => 'text',
                        'placeholder' => 'Ej: ABCD123456XYZ',
                        'required' => true,
                        'regex' => '^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$',
                        'error_message' => 'El RFC no tiene un formato válido.',
                    ],
                    [
                        'key' => 'regimen_fiscal',
                        'label' => 'Régimen Fiscal',
                        'type' => 'select',
                        'options' => [
                            '601' => 'General de Ley Personas Morales',
                            '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
                            '626' => 'Régimen Simplificado de Confianza',
                        ],
                        'required' => true,
                    ]
                ]
            ],
            'AR' => [
                'name' => 'Argentina',
                'fields' => [
                    [
                        'key' => 'cuit',
                        'label' => 'CUIT',
                        'type' => 'text',
                        'placeholder' => 'Ej: 20-12345678-9',
                        'required' => true,
                        'regex' => '^[0-9]{2}-[0-9]{8}-[0-9]$',
                        'error_message' => 'El CUIT debe tener el formato XX-XXXXXXXX-X.',
                    ],
                    [
                        'key' => 'condicion_iva',
                        'label' => 'Condición frente al IVA',
                        'type' => 'select',
                        'options' => [
                            'RI' => 'Responsable Inscripto',
                            'MT' => 'Monotributista',
                            'EX' => 'Exento',
                            'CF' => 'Consumidor Final',
                        ],
                        'required' => true,
                    ]
                ]
            ],
            'US' => [
                'name' => 'Estados Unidos',
                'fields' => [
                    [
                        'key' => 'ein',
                        'label' => 'EIN',
                        'type' => 'text',
                        'placeholder' => 'Ej: 12-3456789',
                        'required' => true,
                        'regex' => '^[0-9]{2}-[0-9]{7}$',
                        'error_message' => 'El EIN debe tener el formato XX-XXXXXXX.',
                    ]
                ]
            ],
            'CO' => [
                'name' => 'Colombia',
                'fields' => [
                    [
                        'key' => 'nit',
                        'label' => 'NIT',
                        'type' => 'text',
                        'placeholder' => 'Ej: 900.123.456-7',
                        'required' => true,
                        'regex' => '^[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}-[0-9]$',
                        'error_message' => 'El NIT debe tener el formato XXXXXXXXX-X.',
                    ],
                    [
                        'key' => 'tipo_contribuyente',
                        'label' => 'Tipo de Contribuyente',
                        'type' => 'select',
                        'options' => [
                            'PJ' => 'Persona Jurídica',
                            'PN' => 'Persona Natural',
                            'GC' => 'Gran Contribuyente',
                        ],
                        'required' => true,
                    ]
                ]
            ],
        ];

        foreach ($countries as $iso => $data) {
            \App\Models\Pais::where('codigo_iso2', $iso)->update([
                'fiscal_fields' => $data['fields']
            ]);
        }
    }
}
