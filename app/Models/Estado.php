<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    protected $table = 'estados';

    protected $fillable = [
        'pais_id',
        'nombre',
        'codigo',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function ciudades(): HasMany
    {
        return $this->hasMany(\App\Models\Ciudad::class, 'estado_id');
    }
}
