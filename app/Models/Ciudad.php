<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'estado_id',
        'nombre',
        'es_capital',
    ];

    protected $casts = [
        'es_capital' => 'boolean',
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}
