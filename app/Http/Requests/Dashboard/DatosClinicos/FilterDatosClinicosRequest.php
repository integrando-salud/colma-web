<?php

namespace App\Http\Requests\Dashboard\DatosClinicos;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:d/m/Y', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date_format:d/m/Y', 'after_or_equal:desde'],
        ];
    }
}
