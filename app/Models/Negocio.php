<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    use SoftDeletes;

    protected $table = 'negocios';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'logo',
        'cover_imagen',
        'telefono',
        'whatsapp',
        'email',
        'sitio_web',
        'direccion',
        'ciudad',
        'pais',
        'latitud',
        'longitud',
        'horario_apertura',
        'duracion_turno_min',
        'anticipacion_min_reserva',
        'cancelacion_limite_horas',
        'plan',
        'plan_vence_en',
        'activo',
        'verificado',
    ];

    protected $casts = [
        'horario_apertura'        => 'array',
        'activo'                  => 'boolean',
        'verificado'              => 'boolean',
        'plan_vence_en'           => 'datetime',
        'latitud'                 => 'decimal:8',
        'longitud'                => 'decimal:8',
        'duracion_turno_min'      => 'integer',
        'anticipacion_min_reserva'=> 'integer',
        'cancelacion_limite_horas'=> 'integer',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function profesionales(): HasMany
    {
        return $this->hasMany(Profesional::class, 'negocio_id');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'negocio_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'negocio_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'negocio_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVerificado($query)
    {
        return $query->where('verificado', true);
    }

    // ─── Helpers ───────────────────────────────────────────────

    /**
     * Verifica si el plan SaaS está vigente.
     */
    public function planVigente(): bool
    {
        if ($this->plan === 'free') {
            return true;
        }

        return $this->plan_vence_en && $this->plan_vence_en->isFuture();
    }
}
