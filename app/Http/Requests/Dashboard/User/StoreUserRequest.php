<?php

namespace App\Http\Requests\Dashboard\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'id' => ['nullable', 'exists:users,id'],
            'nombre' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->id)],
            'password' => ['nullable', 'required_without:id', 'min:6'],
            'rol' => ['required', 'exists:roles,name'],
            'pais_id' => ['required', 'exists:paises,id'],
            'centro_salud_id' => ['nullable', 'required_if:rol,promotor', 'exists:centros_salud,id'],
            'supervisor_id' => ['nullable', 'required_if:rol,promotor', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'El usuario seleccionado no existe.',
            'nombre.required' => 'Ingrese el nombre del usuario.',
            'nombre.max' => 'El nombre del usuario no debe exceder los 255 caracteres.',
            'email.required' => 'Ingrese el correo electrónico del usuario.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'password.required_without' => 'Ingrese la contraseña del usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.required' => 'Seleccione un rol para el usuario.',
            'rol.exists' => 'El rol seleccionado no es válido.',
            'pais_id.required' => 'Seleccione un país para el usuario.',
            'pais_id.exists' => 'El país seleccionado no es válido.',
            'centro_salud_id.exists' => 'El centro de salud seleccionado no es válido.',
            'centro_salud_id.required_if' => 'Seleccione un centro de salud.',
            'supervisor_id.exists' => 'El supervisor seleccionado no es válido.',
            'supervisor_id.required_if' => 'Seleccione un supervisor.',
        ];
    }
}
