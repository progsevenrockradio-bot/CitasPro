<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portafolio extends Model
{
    use SoftDeletes;

    protected $table = 'portafolios';

    protected $fillable = [
        'profesional_id',
        'servicio_id',
        'titulo',
        'descripcion',
        'imagen',
        'imagen_miniatura',
        'tipo',
        'imagen_antes',
        'destacado',
        'publico',
        'orden',
    ];

    protected $casts = [
        'destacado' => 'boolean',
        'publico'   => 'boolean',
        'orden'     => 'integer',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopePublico($query)
    {
        return $query->where('publico', true);
    }

    public function scopeDestacado($query)
    {
        return $query->where('destacado', true)->orderBy('orden');
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('destacado', 'desc')->orderBy('orden');
    }
}
