<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float|string $cantidad
 * @property float|string $precio_unitario
 * @property float|string $descuento_porcentaje
 * @property float|string $iva_porcentaje
 * @property float|string $irpf_porcentaje
 * @property float|string $subtotal
 * @property float|string $impuestos
 * @property float|string $total
 */
class InvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'invoice_lines';

    protected $fillable = [
        'invoice_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento_porcentaje',
        'iva_porcentaje',
        'irpf_porcentaje',
        'subtotal',
        'impuestos',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'descuento_porcentaje' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'irpf_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:4',
        'impuestos' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    /**
     * Relación con la cabecera de la factura.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
