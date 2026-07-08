<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebConfig extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    /**
     * Retrieve a configuration value by key.
     */
    public static function getValue($key, $default = null)
    {
        $config = self::where('key', $key)->first();
        if (!$config) {
            return $default;
        }

        if ($config->type === 'json' || $config->type === 'boolean') {
            return json_decode($config->value, true) ?? $config->value;
        }

        return $config->value;
    }
}
