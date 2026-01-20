<?php

namespace App\Http\Requests\Dashboard\Interacciones;

use App\Models\Interaccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInteraccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
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
            'contactado' => ['required', 'boolean'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'modalidad' => [
                'required',
                'in:' . implode(',', Interaccion::MODALIDADES)
            ],
            'motivo' => [
                'required',
                'in:' . implode(',', array_merge(Interaccion::MOTIVOS_CONTACTO, Interaccion::MOTIVOS_CONTACTO_ALERTA))
            ],
            'observaciones' => [
                'nullable',
                'required_if:alerta_clinica_pregunta_b,No'
            ],
            'motivo_reprogramacion' => [
                'nullable',
                'in:' . implode(',', Interaccion::MOTIVOS_REPROGRAMACION),
                'required_if:contactado,0'
            ],
            'nueva_fecha_contacto' => [
                'nullable',
                'date',
                'required_if:contactado,0'
            ],
            'fecha_cita' => [
                'nullable',
                'date',
                'required_if:motivo,Agendar cita de entrenamiento,Agendar cita final'
            ],
            'lugar_cita' => [
                'nullable',
                'string',
                'required_if:motivo,Agendar cita de entrenamiento,Agendar cita final'
            ],
            'lugar_cita_otro' => [
                'nullable',
                'string',
                'required_if:lugar_cita,Otro'
            ],
            'fecha_cita_final' => [
                'nullable',
                'date',
                'required_if:motivo,Cita de entrenamiento,Cita final,Re-entrenamiento'
            ],
            'hora_cita_final' => [
                'nullable',
                'date_format:H:i',
                'required_if:motivo,Cita de entrenamiento,Cita final,Re-entrenamiento'
            ],
            'lugar_cita_final' => [
                'nullable',
                'string',
                'required_if:motivo,Cita de entrenamiento,Cita final,Re-entrenamiento'
            ],
            'lugar_cita_final_otro' => [
                'nullable',
                'string',
                'required_if:lugar_cita_final,Otro'
            ],
            'seguimiento_pregunta_a' => [
                'nullable',
                'in:Si,No',
                'required_if:motivo,Seguimiento 2 semanas luego del tratamiento,Seguimiento mensual'
            ],
            'seguimiento_pregunta_b' => [
                'nullable',
                'string',
                'required_if:seguimiento_pregunta_a,Si'
            ],
            'seguimiento_pregunta_c' => [
                'nullable',
                'in:Si,No',
                'required_if:seguimiento_pregunta_a,Si'
            ],
            'seguimiento_pregunta_d' => [
                'nullable',
                'string',
                'required_if:seguimiento_pregunta_c,Si'
            ],
            'seguimiento_pregunta_e' => [
                'nullable',
                'in:Si,No',
                'required_if:seguimiento_pregunta_c,No'
            ],
            'seguimiento_pregunta_f' => [
                'nullable',
                'date',
                'required_if:seguimiento_pregunta_e,No'
            ],
            'razon_contacto_participante' => [
                'nullable',
                'string',
                'required_if:motivo,Contacto iniciado por el participante'
            ],
            'razon_contacto_participante_otro' => [
                'nullable',
                'string',
                'required_if:razon_contacto_participante,Otro'
            ],
            'contacto_participante_acciones' => [
                'nullable',
                'string',
                'required_if:motivo,Contacto iniciado por el participante'
            ],
            'no_adherencia_pregunta_a' => [
                'nullable',
                'array',
                'required_if:motivo,No adherencia a las sesiones'
            ],
            'no_adherencia_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return in_array('Otro', $this->input('no_adherencia_pregunta_a', []));
                })
            ],
            'no_adherencia_pregunta_b' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('motivo') === 'No adherencia a las sesiones' && !in_array('Ya no quiere participar en el proyecto', $this->input('no_adherencia_pregunta_a', []));
                })
            ],
            'no_adherencia_pregunta_c' => [
                'nullable',
                'date',
                'required_if:no_adherencia_pregunta_b,No'
            ],
            'problemas_conexion_pregunta_a' => [
                'nullable',
                'array',
                'required_if:motivo,Problemas de conexión'
            ],
            'problemas_conexion_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return in_array('Otro', $this->input('problemas_conexion_pregunta_a', []));
                })
            ],
            'problemas_conexion_pregunta_b' => [
                'nullable',
                'in:Si,No',
                'required_if:motivo,Problemas de conexión'
            ],
            'problemas_conexion_pregunta_c' => [
                'nullable',
                'date',
                'required_if:problemas_conexion_pregunta_b,No'
            ],
            'alerta_clinica_pregunta_a' => [
                'nullable',
                'in:Si,No',
                'required_if:motivo,Alerta clínica'
            ],
            'alerta_clinica_pregunta_b' => [
                'nullable',
                'in:Si,No',
                'required_if:alerta_clinica_pregunta_a,No'
            ],
            'alerta_clinica_pregunta_c' => [
                'nullable',
                'in:Si,No',
                'required_if:alerta_clinica_pregunta_a,Si'
            ],
            'alerta_clinica_pregunta_d' => [
                'nullable',
                'string',
                'required_if:alerta_clinica_pregunta_c,No'
            ],
            'alerta_clinica_pregunta_e' => [
                'nullable',
                'in:Si,No',
                'required_if:alerta_clinica_pregunta_c,Si'
            ],
            'alerta_clinica_pregunta_f' => [
                'nullable',
                'string',
                'required_if:alerta_clinica_pregunta_e,No'
            ],
            'pedido_ayuda_pregunta_a' => [
                'nullable',
                'array',
                'required_if:motivo,Pedido de ayuda a través del aplicativo'
            ],
            'pedido_ayuda_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return in_array('Otro', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_b' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('motivo') === 'Pedido de ayuda a través del aplicativo' && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_c' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('motivo') === 'Pedido de ayuda a través del aplicativo' && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_d' => [
                'nullable',
                'date',
                'required_if:pedido_ayuda_pregunta_c,No'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'required_if' => 'Este campo es obligatorio.',
            'exists' => 'El valor seleccionado no es válido.',
            'integer' => 'Este campo debe ser un número entero.',
            'boolean' => 'El valor seleccionado no es válido.',
            'date' => 'Ingrese una fecha válida.',
            'in' => 'El valor seleccionado no es válido.',
        ];
    }
}
