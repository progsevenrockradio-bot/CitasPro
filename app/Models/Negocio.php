<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Negocio extends Model
{
    use SoftDeletes;

    protected $table = 'negocios';

    protected $fillable = [
        'categoria_id',
        'es_medico',
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
        'ciudad', // Mantenemos por compatibilidad
        'pais',   // Mantenemos por compatibilidad
        'pais_id',
        'estado_id',
        'ciudad_id',
        'municipio',
        'codigo_postal',
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
        'destacado',
        'layout_size',
        'visualizaciones',
        'especialidad',
        'palabras_clave',
        'mp_access_token',
        'redsys_merchant_code',
        'redsys_terminal',
        'redsys_secret_key',
        'whatsapp_modelo',
        'whatsapp_session_instance',
        'whatsapp_session_token',
        'whatsapp_qr_status',
        // Reserva pública online
        'booking_activo',
        'booking_mensaje',
        'telefonos_adicionales',
        'verification_phone_index',
        'numero_fiscal',
        'tipo_clinica',
        // Pasarelas de Pago
        'stripe_public_key',
        'stripe_secret_key',
        'mp_public_key',
        'cobro_online_obligatorio',
        'pasarela_preferida',
    ];

    protected $appends = [
        'public_booking_url',
        'all_phones',
        'verification_phone',
    ];

    protected $casts = [
        'es_medico'               => 'boolean',
        'horario_apertura'        => 'array',
        'activo'                  => 'boolean',
        'verificado'              => 'boolean',
        'destacado'               => 'boolean',
        'booking_activo'          => 'boolean',
        'plan_vence_en'           => 'datetime',
        'latitud'                 => 'decimal:8',
        'longitud'                => 'decimal:8',
        'duracion_turno_min'      => 'integer',
        'anticipacion_min_reserva'=> 'integer',
        'cancelacion_limite_horas'=> 'integer',
        'telefonos_adicionales'   => 'array',
        'verification_phone_index'=> 'integer',
        'visualizaciones'         => 'integer',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function digitalCertificate(): HasOne
    {
        return $this->hasOne(DigitalCertificate::class);
    }

    public function resenas(): HasMany
    {
        return $this->hasMany(Resena::class, 'negocio_id');
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

    /**
     * Clientes que han reservado en este negocio (vía enlace público o panel).
     * Aislados por la tabla pivote negocio_cliente.
     */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'negocio_cliente', 'negocio_id', 'cliente_id')
            ->withPivot(['notas_negocio', 'activo'])
            ->withTimestamps();
    }

    public function datosFiscales()
    {
        return $this->hasOne(NegocioDatosFiscales::class, 'negocio_id');
    }

    public function plantillaHistoriaClinica(): ?PlantillaHistoriaClinica
    {
        if (!$this->tipo_clinica) return null;
        return PlantillaHistoriaClinica::where('tipo', $this->tipo_clinica)
            ->where('activo', true)
            ->first();
    }

    public function entradasHistoriaClinica()
    {
        return $this->hasMany(EntradaHistoriaClinica::class, 'negocio_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeVerificado(Builder $query): Builder
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

    // ─── Helpers de Reserva Pública ────────────────────────────

    /**
     * URL pública para que los clientes finales reserven citas en este negocio.
     */
    public function getPublicBookingUrlAttribute(): string
    {
        return url("/{$this->slug}/book");
    }

    // ─── Ubicación Jerárquica ────────────────────────────

    public function paisObj(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function ciudadObj(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    // ─── Accesores de Teléfonos ───────────────────────────────────

    public function getAllPhonesAttribute(): array
    {
        $phones = [];
        if ($this->telefono) {
            $phones[] = ['number' => $this->telefono, 'type' => 'primary'];
        }
        if ($this->telefonos_adicionales && is_array($this->telefonos_adicionales)) {
            foreach ($this->telefonos_adicionales as $index => $phone) {
                $phones[] = [
                    'number' => $phone['number'] ?? '',
                    'type'   => $phone['type'] ?? 'additional',
                    'index'  => $index,
                ];
            }
        }
        return $phones;
    }

    public function getVerificationPhoneAttribute(): ?string
    {
        if (!is_null($this->verification_phone_index) && isset($this->telefonos_adicionales[$this->verification_phone_index])) {
            return $this->telefonos_adicionales[$this->verification_phone_index]['number'] ?? $this->telefono;
        }
        return $this->telefono;
    }
}
