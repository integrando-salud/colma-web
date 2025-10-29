<?php

namespace App\Http\Requests\Dashboard\Paciente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePacienteRequest extends FormRequest
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
            'id' => ['nullable', 'exists:pacientes,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:F,M'],
            'documento' => ['required', 'max:20', 'unique:pacientes,documento'],
            'telefono' => ['required', 'max:30'],
            'telefono2' => ['nullable', 'max:30'],
            'email' => ['required', 'email', 'max:100', Rule::unique('pacientes', 'email')->ignore($this->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'centro_salud_id' => ['required', 'exists:centros_salud,id'],
            'promotor_id' => ['required', 'exists:users,id'],
            'fecha_enrolamiento' => ['required', 'date'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],
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
            'paquete_id.exists' => 'El paquete seleccionado no es válido.',
            'password.required_without' => 'Ingrese la contraseña del paciente.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'diagnosticos.required' => 'Seleccione al menos un diagnóstico.',
            'diagnosticos.array' => 'Los diagnósticos deben ser un arreglo.',
            'diagnosticos.*.exists' => 'El diagnóstico seleccionado no es válido.',
        ];
    }
}
