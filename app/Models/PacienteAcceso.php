<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteAcceso extends Model
{
    protected $table = 'paciente_accesos';

    protected $fillable = [
        'cliente_id',
        'profesional_id',
        'concedido_por',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    public function concedidoPor(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'concedido_por');
    }
}
