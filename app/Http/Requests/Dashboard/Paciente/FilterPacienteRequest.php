<?php

namespace App\Http\Requests\Dashboard\Paciente;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterPacienteRequest extends FormRequest
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
            'estado' => ['nullable', 'boolean'],
            'diagnostico' => ['nullable', 'exists:App\Models\Diagnostico,id'],
            'alertas' => ['nullable', 'in:clinica,operativa'],
            'nombre' => ['nullable'],
            'orderBy' => ['nullable'],
            'orderDir' => ['nullable', 'in:asc,desc'],
            'promotor' => ['nullable', 'exists:App\Models\User,id'],
        ];
    }
}
