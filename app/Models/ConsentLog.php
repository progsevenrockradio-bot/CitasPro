<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    protected $table = 'consent_logs';

    protected $fillable = [
        'user_id',
        'user_type',
        'document_type',
        'document_version',
        'document_hash',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];
}
