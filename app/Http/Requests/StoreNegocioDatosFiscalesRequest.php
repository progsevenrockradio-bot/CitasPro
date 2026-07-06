<?php

namespace App\Http\Requests;

use App\Models\Pais;
use Illuminate\Foundation\Http\FormRequest;

class StoreNegocioDatosFiscalesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'pais_id' => 'required|exists:paises,id',
            'datos_fiscales' => 'required|array',
        ];

        $paisId = $this->input('pais_id');
        if ($paisId) {
            $pais = Pais::find($paisId);
            if ($pais && $pais->fiscal_fields) {
                foreach ($pais->fiscal_fields as $field) {
                    $fieldRules = [];
                    if (isset($field['required']) && $field['required']) {
                        $fieldRules[] = 'required';
                    } else {
                        $fieldRules[] = 'nullable';
                    }
                    
                    if ($field['type'] === 'text') {
                        $fieldRules[] = 'string';
                    }
                    
                    if (isset($field['regex'])) {
                        $fieldRules[] = 'regex:/' . $field['regex'] . '/';
                    }
                    
                    if (isset($field['options'])) {
                        $fieldRules[] = 'in:' . implode(',', array_keys($field['options']));
                    }

                    $rules['datos_fiscales.' . $field['key']] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];
        $paisId = $this->input('pais_id');
        
        if ($paisId) {
            $pais = Pais::find($paisId);
            if ($pais && $pais->fiscal_fields) {
                foreach ($pais->fiscal_fields as $field) {
                    if (isset($field['error_message'])) {
                        $messages['datos_fiscales.' . $field['key'] . '.regex'] = $field['error_message'];
                    }
                }
            }
        }
        
        return $messages;
    }
}
