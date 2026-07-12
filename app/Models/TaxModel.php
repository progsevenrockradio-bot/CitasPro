<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_models';

    protected $fillable = [
        'negocio_id',
        'modelo',
        'ejercicio',
        'periodo',
        'resultado',
        'resultado_tipo',
        'estado',
        'fecha_presentacion',
        'nrc_justificante',
        'declaracion_desglose',
    ];

    protected $casts = [
        'ejercicio' => 'integer',
        'resultado' => 'decimal:4',
        'fecha_presentacion' => 'datetime',
        'declaracion_desglose' => 'array',
    ];

    /**
     * Relación con el Negocio que realiza la declaración.
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
