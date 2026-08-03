<?php

namespace App\Http\Requests\Dashboard\Paciente;

use App\Models\Paciente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePacienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (! $this->filled('id')) {
            return auth()->user()->can('create', Paciente::class);
        }

        $paciente = Paciente::find($this->input('id'));

        return $paciente !== null && auth()->user()->can('update', $paciente);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'exists:pacientes,id'],
            'id_externo' => ['nullable'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date_format:d/m/Y'],
            'sexo' => ['required', 'in:F,M'],
            'documento' => ['required', 'max:20', Rule::unique('pacientes', 'documento')->ignore($this->id)],
            'telefono' => ['required', 'max:30'],
            'telefono2' => ['nullable', 'max:30'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('pacientes', 'email')->ignore($this->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable'],
            'centro_salud_id' => ['required', 'exists:centros_salud,id'],
            'promotor_id' => ['required', 'exists:users,id'],
            'fecha_enrolamiento' => ['required', 'date_format:d/m/Y'],
            'password' => ['nullable', 'required_without:id', 'min:6'],
            'diagnosticos' => ['required', 'array'],
            'diagnosticos.*' => ['exists:diagnosticos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'max' => 'Máximo :max caracteres.',
            'date' => 'Ingrese una fecha válida.',
            'string' => 'El campo debe ser una cadena de texto.',
            'id.exists' => 'El paciente no es válido.',
            'nombre.required' => 'Ingrese el nombre del paciente.',
            'apellido.required' => 'Ingrese el apellido del paciente.',
            'fecha_nacimiento.required' => 'Ingrese la fecha de nacimiento del paciente.',
            'sexo.required' => 'Seleccione el sexo del paciente.',
            'sexo.in' => 'El valor del campo sexo es inválido.',
            'documento.required' => 'Ingrese el documento del paciente.',
            'documento.max' => 'El documento del paciente no debe exceder los 20 caracteres.',
            'documento.unique' => 'El documento del paciente ya está en uso.',
            'telefono.required' => 'Ingrese el teléfono del paciente.',
            'telefono.max' => 'El número de teléfono debe tener como máximo :max caracteres.',
            'telefono2.max' => 'El número de teléfono 2 debe tener como máximo :max caracteres.',
            'email.required' => 'Ingrese el email del paciente.',
            'email.email' => 'Ingrese un email válido.',
            'direccion.required' => 'Ingrese la dirección del paciente.',
            'referencia.max' => 'La referencia debe tener máximo :max caracteres.',
            'centro_salud_id.required' => 'Seleccione un centro de salud.',
            'centro_salud_id.exists' => 'El centro de salud seleccionado no es válido.',
            'promotor_id.required' => 'Seleccione un promotor.',
            'promotor_id.exists' => 'El promotor seleccionado no es válido.',
            'fecha_enrolamiento.required' => 'Ingrese la fecha de enrolamiento del paciente.',
            'password.required_without' => 'Ingrese la contraseña del paciente.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'diagnosticos.required' => 'Seleccione al menos un diagnóstico.',
            'diagnosticos.array' => 'Los diagnósticos deben ser un arreglo.',
            'diagnosticos.*.exists' => 'El diagnóstico seleccionado no es válido.',
        ];
    }
}
