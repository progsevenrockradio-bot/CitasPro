<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\HasApiTokens;

class Profesional extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'profesionales';

    protected $fillable = [
        'negocio_id',
        'nif',
        'iae',
        'regimen_fiscal',
        'roi',
        'nombre',
        'apellido',
        'telefono',
        'email',
        'password',
        'doble_factor_activo',
        'canal_preferido_2fa', // email | telegram
        'foto',
        'titulo',
        'bio',
        'experiencia_anios',
        'calificacion_promedio',
        'total_resenas',
        'horario_disponible',
        'detalles_opcionales',
        'rol',
        'activo',
        'aceptar_online',
        'telegram_chat_id',
        'notificaciones_telegram',
        'notificaciones_whatsapp',
        'type',
        'google_calendar_token',
        'google_calendar_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'horario_disponible'      => 'array',
        'detalles_opcionales'     => 'array',
        'roi'                     => 'boolean',
        'activo'                  => 'boolean',
        'aceptar_online'          => 'boolean',
        'doble_factor_activo'     => 'boolean',
        'notificaciones_telegram' => 'boolean',
        'notificaciones_whatsapp' => 'boolean',
        'calificacion_promedio'   => 'decimal:2',
        'experiencia_anios'       => 'integer',
        'total_resenas'           => 'integer',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    /**
     * Servicios que este profesional puede ofrecer (M:N con tabla pivot).
     */
    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'profesional_servicio')
            ->withPivot(['precio_override', 'duracion_override_min', 'activo'])
            ->withTimestamps()
            ->wherePivot('activo', true);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'profesional_id');
    }

    public function portafolios(): HasMany
    {
        return $this->hasMany(Portafolio::class, 'profesional_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeGeneral(Builder $query)
    {
        return $query->where('type', 'general');
    }

    public function scopeMedical(Builder $query)
    {
        return $query->where('type', 'medical');
    }

    public function scopeDental(Builder $query)
    {
        return $query->where('type', 'dental');
    }

    public function scopeActivo(Builder $query)
    {
        return $query->where('activo', true);
    }

    public function scopeAceptaOnline(Builder $query)
    {
        return $query->where('aceptar_online', true);
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
