<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float|string $subtotal
 * @property float|string $impuestos
 * @property float|string $total
 * @property float|string $tipo_cambio
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'negocio_id',
        'negocio_datos_fiscales_id',
        'cliente_id',
        'serie',
        'numero',
        'fecha_emision',
        'tipo_factura',
        'estado',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'impuestos',
        'total',
        'hash_anterior',
        'hash_actual',
        'firma',
        'datos_qr',
        'enviado_aeat',
        'fecha_envio_aeat',
        'datos_cliente_snapshot',
        'metadata_adicional',
        'rectifies_invoice_id',
        'rectification_reason',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_envio_aeat' => 'datetime',
        'enviado_aeat' => 'boolean',
        'tipo_cambio' => 'decimal:6',
        'subtotal' => 'decimal:4',
        'impuestos' => 'decimal:4',
        'total' => 'decimal:4',
        'datos_cliente_snapshot' => 'array',
        'metadata_adicional' => 'array',
    ];

    /**
     * Relación con el Negocio que emite la factura.
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Relación con el Cliente al que se le emitió la factura.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con la factura original que esta factura rectifica (si aplica).
     */
    public function rectifiedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'rectifies_invoice_id');
    }

    /**
     * Relación con las facturas rectificativas asociadas a esta factura original.
     */
    public function rectificationInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'rectifies_invoice_id');
    }

    /**
     * Relación con los Datos Fiscales del negocio utilizados al momento de la emisión.
     */
    public function negocioDatosFiscales(): BelongsTo
    {
        return $this->belongsTo(NegocioDatosFiscales::class, 'negocio_datos_fiscales_id');
    }

    /**
     * Relación con las líneas detalladas de la factura.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Helper para comprobar el estado de VeriFactu.
     */
    public function estaVerificada(): bool
    {
        return !is_null($this->hash_actual) && $this->enviado_aeat;
    }
}
