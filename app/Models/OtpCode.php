<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo OtpCode — almacena los códigos de verificación temporales (hasheados).
 */
class OtpCode extends Model
{
    protected $table = 'otp_codes';

    protected $hidden = ['codigo', 'challenge_hash'];

    public ?string $plain_codigo = null;

    protected $fillable = [
        'telefono',
        'email',
        'codigo',
        'tipo',
        'challenge_hash',
        'profesional_id',
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
        'profesional_id' => 'integer',
    ];

    const MAX_INTENTOS = 3;
    const DURACION_MINUTOS = 10;

    public static function hashValor(string $valor): string
    {
        return hash_hmac('sha256', $valor, (string) config('app.key'));
    }

    public function scopeVigente(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('usado', false)
            ->where('expira_en', '>', now())
            ->where('intentos', '<', self::MAX_INTENTOS);
    }

    public function scopeParaTelefono(\Illuminate\Database\Eloquent\Builder $query, string $telefono): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('telefono', $telefono);
    }

    public function scopeParaEmail(\Illuminate\Database\Eloquent\Builder $query, string $email): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('email', $email);
    }

    public function estaExpirado(): bool
    {
        return $this->expira_en->isPast();
    }

    public function superioLimiteIntentos(): bool
    {
        return $this->intentos >= self::MAX_INTENTOS;
    }

    public function esValido(): bool
    {
        return !$this->usado
            && !$this->estaExpirado()
            && !$this->superioLimiteIntentos();
    }

    public function verificarCodigo(string $plainCode): bool
    {
        return hash_equals((string) $this->codigo, static::hashValor($plainCode));
    }

    public function verificarChallenge(?string $plainChallenge, int $profesionalId): bool
    {
        if (empty($plainChallenge) || empty($this->challenge_hash) || (int) $this->profesional_id !== $profesionalId) {
            return false;
        }
        return hash_equals((string) $this->challenge_hash, static::hashValor($plainChallenge));
    }

    public function registrarIntentoFallido(): void
    {
        $this->increment('intentos');
        if ($this->fresh()->intentos >= self::MAX_INTENTOS) {
            $this->update(['usado' => true]);
        }
    }

    public function marcarComoUsado(): void
    {
        $this->update([
            'usado'    => true,
            'usado_en' => now(),
        ]);
    }

    public static function generarCodigo(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function crearParaTelefono(
        ?string $telefono,
        string $tipo = 'login',
        ?string $ip = null,
        ?string $challengeHash = null,
        ?int $profesionalId = null
    ): static {
        if ($telefono !== null) {
            static::where('telefono', $telefono)
                ->where('usado', false)
                ->update(['usado' => true]);
        }

        $plain = static::generarCodigo();
        $record = static::create([
            'telefono'       => $telefono,
            'email'          => null,
            'codigo'         => static::hashValor($plain),
            'tipo'           => $tipo,
            'challenge_hash' => $challengeHash,
            'profesional_id' => $profesionalId,
            'usado'          => false,
            'intentos'       => 0,
            'ip_solicitante' => $ip,
            'expira_en'      => now()->addMinutes(static::DURACION_MINUTOS),
        ]);
        $record->plain_codigo = $plain;
        return $record;
    }

    public static function crearParaEmail(
        string $email,
        string $tipo = 'login',
        ?string $ip = null,
        ?string $challengeHash = null,
        ?int $profesionalId = null
    ): static {
        $email = strtolower(trim($email));

        static::where('email', $email)
            ->where('usado', false)
            ->update(['usado' => true]);

        $plain = static::generarCodigo();
        $record = static::create([
            'telefono'       => null,
            'email'          => $email,
            'codigo'         => static::hashValor($plain),
            'tipo'           => $tipo,
            'challenge_hash' => $challengeHash,
            'profesional_id' => $profesionalId,
            'usado'          => false,
            'intentos'       => 0,
            'ip_solicitante' => $ip,
            'expira_en'      => now()->addMinutes(static::DURACION_MINUTOS),
        ]);
        $record->plain_codigo = $plain;
        return $record;
    }
}
