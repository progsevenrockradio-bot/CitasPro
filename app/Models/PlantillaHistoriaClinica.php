<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaHistoriaClinica extends Model
{
    protected $table = 'plantillas_historia_clinica';

    protected $fillable = [
        'nombre',
        'tipo',
        'campos',
        'activo',
    ];

    protected $casts = [
        'campos'  => 'array',
        'activo'  => 'boolean',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaHistoriaClinica::class, 'plantilla_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeParaTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ─── Helpers ───────────────────────────────────────────────

    /**
     * Genera reglas de validación de Laravel a partir de los campos del template.
     * Acepta respuestas bajo la clave 'respuestas_clinicas.{key}'.
     */
    public function buildValidationRules(): array
    {
        $rules = [];
        foreach ($this->campos as $campo) {
            $key = 'respuestas_clinicas.' . $campo['key'];
            $fieldRules = [];

            // Si el campo tiene depends_on, hacerlo nullable condicionalmente
            if (!empty($campo['depends_on'])) {
                $fieldRules[] = 'nullable';
            } else {
                $fieldRules[] = (!empty($campo['required']) && $campo['required']) ? 'required' : 'nullable';
            }

            // Tipo de dato
            switch ($campo['type'] ?? 'text') {
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'radio':
                case 'select':
                    if (!empty($campo['options'])) {
                        $fieldRules[] = 'in:' . implode(',', array_map(fn($o) => is_array($o) ? $o['value'] : $o, $campo['options']));
                    }
                    break;
                case 'checkbox':
                case 'odontograma':
                case 'odontograma_anatomico':
                case 'esquema_mamario':
                    $fieldRules[] = 'array';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:2000';
            }

            $rules[$key] = $fieldRules;
        }
        return $rules;
    }
}
