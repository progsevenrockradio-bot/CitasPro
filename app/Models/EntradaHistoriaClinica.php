<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntradaHistoriaClinica extends Model
{
    use SoftDeletes;

    protected $table = 'entradas_historia_clinica';

    protected $fillable = [
        'negocio_id',
        'cliente_id',
        'plantilla_id',
        'cita_id',
        'respuestas',
    ];

    protected $casts = [
        'respuestas' => 'array',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaHistoriaClinica::class, 'plantilla_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }
}
