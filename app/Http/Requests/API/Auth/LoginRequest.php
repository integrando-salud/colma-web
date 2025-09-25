<?php

namespace App\Http\Requests\API\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required'],
            'tipo_sesion' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El campo email es obligatorio',
            'email.email' => 'El campo email debe ser una dirección de correo electrónico válida',
            'password.required' => 'El campo password es obligatorio',
            'tipo_sesion.required' => 'El campo tipo_sesion es obligatorio',
        ];
    }
}
