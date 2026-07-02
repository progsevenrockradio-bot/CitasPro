<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaClinica extends Model
{
    use SoftDeletes;

    protected $table = 'fichas_clinicas';

    protected $fillable = [
        'cliente_id',
        'profesional_id',
        'cita_id',
        'motivo_consulta',
        'diagnostico',
        'tratamiento',
        'receta',
        'notas',
    ];

    protected $casts = [
        'motivo_consulta' => 'encrypted',
        'diagnostico'     => 'encrypted',
        'tratamiento'     => 'encrypted',
        'receta'          => 'encrypted',
        'notas'           => 'encrypted',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }
}
