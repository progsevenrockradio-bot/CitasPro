<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'condiciones_medicas',
        'activo',
        'acepta_marketing',
        'telefono_verificado_en',
        'telegram_chat_id',
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

    public function formularioIngreso(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FormularioIngreso::class, 'cliente_id');
    }

    public function fichasClinicas(): HasMany
    {
        return $this->hasMany(FichaClinica::class, 'cliente_id');
    }

    public function accesosCompartidos(): HasMany
    {
        return $this->hasMany(PacienteAcceso::class, 'cliente_id');
    }

    /**
     * Negocios donde este cliente ha reservado (vía enlace público o panel).
     */
    public function negocios(): BelongsToMany
    {
        return $this->belongsToMany(Negocio::class, 'negocio_cliente', 'cliente_id', 'negocio_id')
            ->withPivot(['notas_negocio', 'activo'])
            ->withTimestamps();
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

    /**
     * Verifica si este cliente está vinculado a un negocio específico.
     */
    public function perteneceANegocio(int $negocioId): bool
    {
        return $this->negocios()->where('negocio_id', $negocioId)->exists();
    }
}
