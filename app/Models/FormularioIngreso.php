<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormularioIngreso extends Model
{
    protected $table = 'formularios_ingreso';

    protected $fillable = [
        'cliente_id',
        'antecedentes_medicos',
        'antecedentes_familiares',
        'alergias',
        'medicacion_actual',
        'tipo_sangre',
        'firmado_consentimiento',
    ];

    protected $casts = [
        'antecedentes_medicos'    => 'encrypted',
        'antecedentes_familiares' => 'encrypted',
        'alergias'                => 'encrypted',
        'medicacion_actual'       => 'encrypted',
        'firmado_consentimiento'  => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
