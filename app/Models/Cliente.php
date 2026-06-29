<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo Cliente — usuario final que reserva citas.
 * Se autentica via OTP (número de celular), no usa contraseña.
 * Implementa HasApiTokens para que Sanctum emita tokens.
 */
class Cliente extends Model
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'email',
        'foto',
        'fecha_nacimiento',
        'genero',
        'pais',
        'notas_internas',
        'activo',
        'acepta_marketing',
        'telefono_verificado_en',
    ];

    protected $casts = [
        'activo'                   => 'boolean',
        'acepta_marketing'         => 'boolean',
        'fecha_nacimiento'         => 'date',
        'telefono_verificado_en'   => 'datetime',
    ];

    protected $hidden = [
        'notas_internas',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'cliente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_id');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'telefono', 'telefono');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVerificado($query)
    {
        return $query->whereNotNull('telefono_verificado_en');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    public function telefonoVerificado(): bool
    {
        return !is_null($this->telefono_verificado_en);
    }
}
