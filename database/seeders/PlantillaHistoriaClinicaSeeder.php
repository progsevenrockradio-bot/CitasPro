<?php

namespace Database\Seeders;

use App\Models\PlantillaHistoriaClinica;
use Illuminate\Database\Seeder;

class PlantillaHistoriaClinicaSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════
        // HISTORIA CLÍNICA MÉDICA
        // Extraída de formulario físico: Historia Clínica General
        // ═══════════════════════════════════════════════════════
        $camposMedicos = [
            // ── Sección: Datos del Paciente ──
            ['section' => 'Datos del Paciente', 'key' => 'nombre_completo',       'label' => 'Nombre completo',                     'type' => 'text',     'required' => true,  'placeholder' => 'Juan Pérez'],
            ['section' => 'Datos del Paciente', 'key' => 'edad',                  'label' => 'Edad',                                'type' => 'number',   'required' => true,  'placeholder' => '30'],
            ['section' => 'Datos del Paciente', 'key' => 'sexo',                  'label' => 'Sexo',                                'type' => 'radio',    'required' => true,  'options' => ['Masculino', 'Femenino']],
            ['section' => 'Datos del Paciente', 'key' => 'ocupacion',             'label' => 'Ocupación',                           'type' => 'text',     'required' => false],
            ['section' => 'Datos del Paciente', 'key' => 'direccion',             'label' => 'Dirección',                           'type' => 'text',     'required' => false],
            ['section' => 'Datos del Paciente', 'key' => 'telefono',              'label' => 'Teléfono',                            'type' => 'text',     'required' => false],

            // ── Sección: Motivo de Consulta ──
            ['section' => 'Motivo de Consulta', 'key' => 'motivo_consulta',       'label' => 'Motivo de consulta',                  'type' => 'textarea', 'required' => true,  'placeholder' => 'Describa el motivo de su visita...'],

            // ── Sección: Antecedentes Personales ──
            ['section' => 'Antecedentes Personales', 'key' => 'tiene_alergias',        'label' => '¿Tiene alergias?',               'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Antecedentes Personales', 'key' => 'alergias_detalle',      'label' => 'Especifique las alergias',        'type' => 'textarea', 'required' => false, 'depends_on' => ['field' => 'tiene_alergias', 'value' => 'Sí']],
            ['section' => 'Antecedentes Personales', 'key' => 'antecedentes_quirurgicos','label' => 'Antecedentes quirúrgicos',      'type' => 'textarea', 'required' => false, 'placeholder' => 'Cirugías previas...'],
            ['section' => 'Antecedentes Personales', 'key' => 'patologias',            'label' => 'Patologías previas',              'type' => 'textarea', 'required' => false, 'placeholder' => 'Enfermedades crónicas u otras patologías...'],
            ['section' => 'Antecedentes Personales', 'key' => 'antecedentes_ginecologicos','label' => 'Antecedentes ginecológicos', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Embarazos, última menstruación, métodos anticonceptivos...'],

            // ── Sección: Examen Físico ──
            ['section' => 'Examen Físico', 'key' => 'examen_cabeza',          'label' => 'Cabeza',          'type' => 'textarea', 'required' => false],
            ['section' => 'Examen Físico', 'key' => 'examen_torax',           'label' => 'Tórax',           'type' => 'textarea', 'required' => false],
            ['section' => 'Examen Físico', 'key' => 'examen_mamas',           'label' => 'Examen de Glándulas Mamarias', 'type' => 'esquema_mamario', 'required' => false],
            ['section' => 'Examen Físico', 'key' => 'examen_abdomen',         'label' => 'Abdomen',         'type' => 'textarea', 'required' => false],
            ['section' => 'Examen Físico', 'key' => 'examen_extremidades',    'label' => 'Extremidades',    'type' => 'textarea', 'required' => false],
            ['section' => 'Examen Físico', 'key' => 'signos_vitales',         'label' => 'Signos Vitales',  'type' => 'text',     'required' => false, 'placeholder' => 'TA: PA: FC: FR:'],

            // ── Sección: Diagnóstico ──
            ['section' => 'Diagnóstico',   'key' => 'examenes_complementarios','label' => 'Exámenes Complementarios', 'type' => 'textarea', 'required' => false],
            ['section' => 'Diagnóstico',   'key' => 'evaluacion_clinica',       'label' => 'Evaluación Clínica / Impresión Diagnóstica', 'type' => 'textarea', 'required' => false],
        ];

        // ═══════════════════════════════════════════════════════
        // HISTORIA CLÍNICA DENTAL
        // Extraída de formulario físico: Historia Clínica Dental
        // ═══════════════════════════════════════════════════════
        $camposDentales = [
            // ── Sección: Datos Personales ──
            ['section' => 'Datos Personales', 'key' => 'nombre_apellido',         'label' => 'Nombre y Apellido',                   'type' => 'text',     'required' => true,  'placeholder' => 'Juan Pérez'],
            ['section' => 'Datos Personales', 'key' => 'edad',                    'label' => 'Edad',                                'type' => 'number',   'required' => true],
            ['section' => 'Datos Personales', 'key' => 'sexo',                    'label' => 'Sexo',                                'type' => 'radio',    'required' => true,  'options' => ['M', 'F']],
            ['section' => 'Datos Personales', 'key' => 'lugar_fecha_nacimiento',  'label' => 'Lugar y Fecha de Nacimiento',         'type' => 'text',     'required' => false],
            ['section' => 'Datos Personales', 'key' => 'procedencia',             'label' => 'Procedencia',                         'type' => 'text',     'required' => false],
            ['section' => 'Datos Personales', 'key' => 'direccion',               'label' => 'Dirección',                           'type' => 'text',     'required' => false],
            ['section' => 'Datos Personales', 'key' => 'telefono',                'label' => 'Teléfono',                            'type' => 'text',     'required' => false],
            ['section' => 'Datos Personales', 'key' => 'ocupacion',               'label' => 'Ocupación',                           'type' => 'text',     'required' => false],

            // ── Sección: Motivo de Consulta ──
            ['section' => 'Motivo de Consulta', 'key' => 'motivo_consulta',      'label' => 'Motivo de Consulta',                   'type' => 'textarea', 'required' => true,  'placeholder' => 'Describa el motivo de su visita dental...'],
            ['section' => 'Motivo de Consulta', 'key' => 'referido_por',         'label' => 'Referido Por',                         'type' => 'text',     'required' => false],

            // ── Sección: Antecedentes Médicos ──
            ['section' => 'Antecedentes',     'key' => 'en_tratamiento_medico',  'label' => '¿Está en tratamiento médico?',         'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Antecedentes',     'key' => 'tratamiento_tipo',       'label' => 'Tipo de tratamiento',                  'type' => 'text',     'required' => false, 'depends_on' => ['field' => 'en_tratamiento_medico', 'value' => 'Sí']],
            ['section' => 'Antecedentes',     'key' => 'toma_medicamentos',      'label' => '¿Toma medicamentos?',                  'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Antecedentes',     'key' => 'medicamentos_cuales',    'label' => '¿Cuáles medicamentos?',                'type' => 'text',     'required' => false, 'depends_on' => ['field' => 'toma_medicamentos', 'value' => 'Sí']],
            ['section' => 'Antecedentes',     'key' => 'alergico_penicilina',    'label' => '¿Alérgico a la penicilina u otro medicamento?', 'type' => 'radio', 'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Antecedentes',     'key' => 'alergia_cuales',         'label' => '¿A cuáles medicamentos?',              'type' => 'text',     'required' => false, 'depends_on' => ['field' => 'alergico_penicilina', 'value' => 'Sí']],
            ['section' => 'Antecedentes',     'key' => 'complicaciones_anestesia','label' => '¿Ha tenido complicaciones por anestesia en la boca?', 'type' => 'radio', 'required' => false, 'options' => ['Sí', 'No', 'No Sabe']],
            ['section' => 'Antecedentes',     'key' => 'propenso_hemorragias',   'label' => '¿Propenso a hemorragias?',             'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No', 'No Sabe']],
            ['section' => 'Antecedentes',     'key' => 'embarazo',               'label' => '¿Embarazo?',                           'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No', 'No Sabe']],

            // ── Sección: Patologías ──
            ['section' => 'Patologías',       'key' => 'diabetes',               'label' => 'Diabetes',                             'type' => 'text',     'required' => false, 'placeholder' => 'Valor o No'],
            ['section' => 'Patologías',       'key' => 'tension_arterial',       'label' => 'T.A. (Tensión Arterial)',              'type' => 'text',     'required' => false, 'placeholder' => '120/80 mmHg'],
            ['section' => 'Patologías',       'key' => 'cardiopatia',            'label' => 'Cardiopatía',                          'type' => 'text',     'required' => false],
            ['section' => 'Patologías',       'key' => 'hepatitis',              'label' => 'Hepatitis',                            'type' => 'text',     'required' => false],
            ['section' => 'Patologías',       'key' => 'herpes',                 'label' => 'Herpes',                               'type' => 'text',     'required' => false],
            ['section' => 'Patologías',       'key' => 'habitos',                'label' => 'Hábitos',                              'type' => 'textarea', 'required' => false, 'placeholder' => 'Tabaco, alcohol, drogas...'],
            ['section' => 'Patologías',       'key' => 'otras_condiciones',      'label' => 'Otras condiciones médicas',            'type' => 'textarea', 'required' => false],

            // ── Sección: Examen Clínico Bucal ──
            ['section' => 'Examen Clínico Bucal', 'key' => 'estado_odontograma', 'label' => 'Odontograma',                         'type' => 'odontograma','required' => false],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_removible_sup', 'label' => 'Prótesis Removible Superior',     'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_removible_inf', 'label' => 'Prótesis Removible Inferior',     'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_fija_sup',      'label' => 'Prótesis Fija Superior',          'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_fija_inf',      'label' => 'Prótesis Fija Inferior',          'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_total_sup',     'label' => 'Prótesis Total Superior',         'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'protesis_total_inf',     'label' => 'Prótesis Total Inferior',         'type' => 'radio',    'required' => false, 'options' => ['Sí', 'No']],
            ['section' => 'Examen Clínico Bucal', 'key' => 'observaciones_bucales',  'label' => 'Observaciones / Estado Bucal General', 'type' => 'textarea', 'required' => false],
        ];

        PlantillaHistoriaClinica::updateOrCreate(
            ['tipo' => 'medical'],
            [
                'nombre' => 'Historia Clínica Médica',
                'tipo'   => 'medical',
                'campos' => $camposMedicos,
                'activo' => true,
            ]
        );

        PlantillaHistoriaClinica::updateOrCreate(
            ['tipo' => 'dental'],
            [
                'nombre' => 'Historia Clínica Dental',
                'tipo'   => 'dental',
                'campos' => $camposDentales,
                'activo' => true,
            ]
        );

        $this->command->info('✔ Plantillas de historia clínica insertadas correctamente.');
    }
}
