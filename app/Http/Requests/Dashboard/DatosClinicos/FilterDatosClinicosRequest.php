<?php

namespace App\Http\Requests\Dashboard\DatosClinicos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterDatosClinicosRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:d/m/Y', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date_format:d/m/Y', 'after_or_equal:desde'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_format' => 'Ingrese una fecha válida en formato DD/MM/AAAA.',
            'before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin.',
            'after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
