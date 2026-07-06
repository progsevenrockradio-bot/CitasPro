<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NegocioDatosFiscales extends Model
{
    use HasFactory;

    protected $table = 'negocio_datos_fiscales';

    protected $fillable = [
        'negocio_id',
        'pais_id',
        'datos_fiscales',
        'verificado',
    ];

    protected $casts = [
        'datos_fiscales' => 'array',
        'verificado' => 'boolean',
    ];

    public function negocio(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function pais(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }
}
