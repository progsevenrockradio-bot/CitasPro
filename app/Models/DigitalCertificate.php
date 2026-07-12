<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalCertificate extends Model
{
    use HasFactory;

    protected $table = 'digital_certificates';

    protected $fillable = [
        'negocio_id',
        'encrypted_certificate',
        'encrypted_password',
        'common_name',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    /**
     * Relación con el Negocio que posee el certificado.
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
