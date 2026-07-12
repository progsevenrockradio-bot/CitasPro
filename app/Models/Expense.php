<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'negocio_id',
        'proveedor_nombre',
        'proveedor_nif',
        'concepto',
        'fecha_gasto',
        'categoria',
        'subtotal',
        'iva_porcentaje',
        'impuestos',
        'total',
        'afectacion_porcentaje',
        'importe_deducible',
        'es_bien_inversion',
        'documento_adjunto_path',
        'detalles_categoria',
        'detalles_opcionales',
    ];

    protected $casts = [
        'fecha_gasto' => 'datetime',
        'subtotal' => 'decimal:4',
        'iva_porcentaje' => 'decimal:2',
        'impuestos' => 'decimal:4',
        'total' => 'decimal:4',
        'afectacion_porcentaje' => 'decimal:2',
        'importe_deducible' => 'decimal:4',
        'es_bien_inversion' => 'boolean',
        'detalles_categoria' => 'array',
        'detalles_opcionales' => 'array',
    ];

    /**
     * Relación con el Negocio que registra el gasto.
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
