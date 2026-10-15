<?php

namespace App\Http\Requests\Dashboard\Paciente;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updatePassword', $this->route('paciente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Ingrese la nueva contraseña del paciente.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
