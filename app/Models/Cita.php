<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    use SoftDeletes;

    protected $table = 'citas';

    protected $fillable = [
        'codigo_referencia',
        'negocio_id',
        'cliente_id',
        'profesional_id',
        'servicio_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_min',
        'estado',
        'precio_total',
        'moneda',
        'recordatorio_enviado',
        'recordatorio_enviado_en',
        'notas_cliente',
        'notas_profesional',
        'canal',
    ];

    protected $casts = [
        'fecha'                   => 'date',
        'precio_total'            => 'decimal:2',
        'recordatorio_enviado'    => 'boolean',
        'recordatorio_enviado_en' => 'datetime',
        'duracion_min'            => 'integer',
    ];

    // ─── Boot ──────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Genera el código de referencia único al crear la cita
        static::creating(function (Cita $cita) {
            $cita->codigo_referencia = 'CIT-' . date('Y') . '-' . str_pad(
                (static::withTrashed()->count() + 1),
                5,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    // ─── Relaciones ────────────────────────────────────────────

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class, 'cita_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeConfirmada($query)
    {
        return $query->where('estado', 'confirmada');
    }

    public function scopeProximas($query)
    {
        return $query->where('fecha', '>=', now()->toDateString())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio');
    }

    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha', today())
            ->orderBy('hora_inicio');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function estaCancelable(): bool
    {
        $limiteHoras = $this->negocio->cancelacion_limite_horas ?? 24;
        $fechaHoraInicio = \Carbon\Carbon::parse("{$this->fecha} {$this->hora_inicio}");

        return now()->diffInHours($fechaHoraInicio, false) >= $limiteHoras
            && in_array($this->estado, ['pendiente', 'confirmada']);
    }

    public function estaPagada(): bool
    {
        return $this->pago && $this->pago->estado === 'completado';
    }
}
