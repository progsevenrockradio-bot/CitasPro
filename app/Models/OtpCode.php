<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modelo OtpCode — almacena los códigos de verificación temporales.
 * Usados para el flujo de login sin contraseña (passwordless OTP).
 */
class OtpCode extends Model
{
    protected $table = 'otp_codes';

    protected $fillable = [
        'telefono',
        'codigo',
        'tipo',
        'usado',
        'intentos',
        'ip_solicitante',
        'expira_en',
        'usado_en',
    ];

    protected $casts = [
        'usado'     => 'boolean',
        'expira_en' => 'datetime',
        'usado_en'  => 'datetime',
        'intentos'  => 'integer',
    ];

    // Máximo de intentos antes de invalidar el código
    const MAX_INTENTOS = 3;

    // Duración del OTP en minutos
    const DURACION_MINUTOS = 10;

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeVigente($query)
    {
        return $query->where('usado', false)
            ->where('expira_en', '>', now())
            ->where('intentos', '<', self::MAX_INTENTOS);
    }

    public function scopeParaTelefono($query, string $telefono)
    {
        return $query->where('telefono', $telefono);
    }

    // ─── Helpers ───────────────────────────────────────────────

    /**
     * ¿El código ha expirado?
     */
    public function estaExpirado(): bool
    {
        return $this->expira_en->isPast();
    }

    /**
     * ¿Se superó el límite de intentos?
     */
    public function superioLimiteIntentos(): bool
    {
        return $this->intentos >= self::MAX_INTENTOS;
    }

    /**
     * ¿El código es válido y puede usarse?
     */
    public function esValido(): bool
    {
        return !$this->usado
            && !$this->estaExpirado()
            && !$this->superioLimiteIntentos();
    }

    /**
     * Incrementa intentos fallidos.
     */
    public function registrarIntentoFallido(): void
    {
        $this->increment('intentos');
    }

    /**
     * Marca el código como usado.
     */
    public function marcarComoUsado(): void
    {
        $this->update([
            'usado'    => true,
            'usado_en' => now(),
        ]);
    }

    /**
     * Genera un nuevo código de 6 dígitos.
     */
    public static function generarCodigo(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un nuevo OTP para el teléfono dado.
     * Invalida los anteriores del mismo teléfono.
     */
    public static function crearParaTelefono(string $telefono, string $tipo = 'login', ?string $ip = null): static
    {
        // Invalida los códigos anteriores
        static::where('telefono', $telefono)
            ->where('usado', false)
            ->update(['usado' => true]);

        return static::create([
            'telefono'       => $telefono,
            'codigo'         => static::generarCodigo(),
            'tipo'           => $tipo,
            'usado'          => false,
            'intentos'       => 0,
            'ip_solicitante' => $ip,
            'expira_en'      => now()->addMinutes(static::DURACION_MINUTOS),
        ]);
    }
}
