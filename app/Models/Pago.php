<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'cita_id',
        'cliente_id',
        'negocio_id',
        'referencia_externa',
        'monto',
        'descuento',
        'impuesto',
        'monto_total',
        'moneda',
        'metodo',
        'estado',
        'notas',
        'pagado_en',
        'metadata',
        'es_sena',
    ];

    protected $casts = [
        'monto'        => 'decimal:2',
        'descuento'    => 'decimal:2',
        'impuesto'     => 'decimal:2',
        'monto_total'  => 'decimal:2',
        'pagado_en'    => 'datetime',
        'metadata'     => 'array',
        'es_sena'      => 'boolean',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeCompletado($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function estaCompletado(): bool
    {
        return $this->estado === 'completado';
    }

    /**
     * Calcula el total como: monto - descuento + impuesto
     */
    public function calcularTotal(): float
    {
        return round(
            (float) $this->monto - (float) $this->descuento + (float) $this->impuesto,
            2
        );
    }
}
