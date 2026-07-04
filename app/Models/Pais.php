<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'paises';
    
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'nombre_en',
        'codigo_iso2',
        'codigo_iso3',
        'prefijo',
        'bandera',
        'region',
        'orden_preferencia',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden_preferencia' => 'integer',
    ];
}
