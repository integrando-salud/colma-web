<?php

namespace App\Http\Requests\Dashboard\Interacciones;

use App\Models\Interaccion;
use Illuminate\Foundation\Http\FormRequest;

class StoreInteraccionRequest extends FormRequest
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
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'alerta_id' => ['nullable', 'integer'],
            'alerta_tipo' => ['nullable', 'in:' . implode(',', Interaccion::TIPOS_ALERTA)],
            'contactado' => ['required', 'boolean'],
            'razon_no_contacto' => ['nullable', 'in:' . implode(',', Interaccion::RAZONES_NO_CONTACTO)],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'modalidad' => ['required', 'in:' . implode(',', Interaccion::MODALIDADES)],
            'motivo' => ['required', 'in:' . implode(',', Interaccion::MOTIVOS_CONTACTO)],
            'observaciones' => ['nullable'],
            'accion' => ['nullable', 'in:' . implode(',', Interaccion::ACCIONES)],
            'fecha_reprogramacion' => ['nullable', 'date'],
            'motivo_reprogramacion' => ['nullable', 'in:' . implode(',', Interaccion::MOTIVOS_REPROGRAMACION)]
        ];
    }

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El campo paciente es obligatorio.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'alerta_id.integer' => 'El campo alerta_id debe ser un número entero.',
            'alerta_tipo.in' => 'El campo alerta tipo debe ser una opción de las siguientes: ' . implode(',', Interaccion::TIPOS_ALERTA),
            'contactado.required' => 'El campo resultado del contacto es obligatorio.',
            'contactado.boolean' => 'El campo resultado del contacto debe ser 0 o 1',
            'razon_no_contacto.in' => 'El campo razón de no contacto debe ser una opción de las siguientes: ' . implode(',', Interaccion::RAZONES_NO_CONTACTO),
            'fecha.required' => 'El campo fecha de contacto es obligatorio.',
            'fecha.date' => 'El campo fecha de contacto debe ser una fecha válida',
            'hora.required' => 'El campo hora de contacto es obligatorio.',
            'hora.date' => 'El campo hora de contacto debe ser una hora válida',
            'modalidad.required' => 'El campo modalidad es obligatorio.',
            'modalidad.in' => 'El campo modalidad debe ser una opción de las siguientes: ' . implode(',', Interaccion::MODALIDADES),
            'motivo.in' => 'El campo motivo debe ser una opción de las siguientes: ' . implode(',', Interaccion::MOTIVOS_CONTACTO),
            'accion' => 'El campo acción debe ser una opción de las siguiente: ' . implode(',', Interaccion::ACCIONES),
            'fecha_reprogramacion.date' => 'El campo fecha de reprogramación debe ser una fecha válida',
            'motivo_reprogramacion.in' => 'El campo motivo de reprogramación debe ser una opción de las siguientes: ' . implode(',', Interaccion::MOTIVOS_REPROGRAMACION),
        ];
    }
}
