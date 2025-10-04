<?php

namespace App\Http\Requests\Dashboard\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'nombre' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'rol_id' => ['required', 'exists:roles,id'],
            'pais_id' => ['required', 'exists:paises,id'],
            'centro_salud_id' => ['nullable', 'exists:centros_salud,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Ingrese el nombre del usuario.',
            'nombre.max' => 'El nombre del usuario no debe exceder los 255 caracteres.',
            'email.required' => 'Ingrese el correo electrónico del usuario.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'password.required' => 'Ingrese la contraseña del usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol_id.required' => 'Seleccione un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'pais_id.required' => 'Seleccione un país para el usuario.',
            'pais_id.exists' => 'El país seleccionado no es válido.',
            'centro_salud_id.exists' => 'El centro de salud seleccionado no es válido.'
        ];
    }
}
