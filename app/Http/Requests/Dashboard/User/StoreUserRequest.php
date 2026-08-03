<?php

namespace App\Http\Requests\Dashboard\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->filled('id')
            ? auth()->user()->can('editar-usuarios')
            : auth()->user()->can('crear-usuarios');
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
            'sexo' => ['required', 'in:F,M'],
            'documento' => ['required', 'max:20', Rule::unique('users', 'documento')->ignore($this->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->id)],
            'password' => ['nullable', 'required_without:id', 'min:6'],
            'rol' => ['required', 'exists:roles,name'],
            'pais_id' => ['required', 'exists:paises,id'],
            'centro_salud_id' => [
                'nullable',
                'required_if:rol,promotor',
                Rule::exists('centros_salud', 'id')->where('pais_id', $this->input('pais_id')),
            ],
            'supervisor_id' => ['nullable', 'required_if:rol,promotor', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('rol') !== 'promotor' || empty($this->input('supervisor_id'))) {
            return;
        }

        $supervisor = User::find($this->input('supervisor_id'));

        if (!$supervisor) {
            return;
        }

        $this->merge([
            'pais_id' => $supervisor->pais_id,
        ]);
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'El usuario seleccionado no existe.',
            'nombre.required' => 'Ingrese el nombre del usuario.',
            'nombre.max' => 'El nombre del usuario no debe exceder los 255 caracteres.',
            'sexo.required' => 'Seleccione el sexo del usuario.',
            'sexo.in' => 'El valor del campo sexo es inválido.',
            'documento.required' => 'Ingrese el documento del usuario.',
            'documento.unique' => 'El documento del usuario ya está en uso.',
            'documento.max' => 'El documento del usuario no debe exceder los 20 caracteres.',
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
