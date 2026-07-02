<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'servicios';

    protected $fillable = [
        'negocio_id',
        'nombre',
        'descripcion',
        'imagen',
        'duracion_min',
        'precio',
        'moneda',
        'precio_desde',
        'max_clientes_simultaneous',
        'categoria_servicio',
        'activo',
        'orden',
        'requiere_sena',
        'tipo_sena',
        'valor_sena',
    ];

    protected $casts = [
        'precio'                    => 'decimal:2',
        'precio_desde'              => 'boolean',
        'activo'                    => 'boolean',
        'duracion_min'              => 'integer',
        'max_clientes_simultaneous' => 'integer',
        'orden'                     => 'integer',
        'requiere_sena'             => 'boolean',
        'valor_sena'                => 'decimal:2',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    /**
     * Profesionales que ofrecen este servicio (M:N con tabla pivot).
     */
    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(Profesional::class, 'profesional_servicio')
            ->withPivot(['precio_override', 'duracion_override_min', 'activo'])
            ->withTimestamps()
            ->wherePivot('activo', true);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'servicio_id');
    }

    public function portafolios(): HasMany
    {
        return $this->hasMany(Portafolio::class, 'servicio_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function getPrecioFormateadoAttribute(): string
    {
        $prefijo = $this->precio_desde ? 'Desde ' : '';
        return "{$prefijo}{$this->precio} {$this->moneda}";
    }
}
