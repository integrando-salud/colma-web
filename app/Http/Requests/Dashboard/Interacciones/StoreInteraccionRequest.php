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
            'id' => ['nullable', 'exists:interacciones,id'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'alerta_id' => ['nullable', 'integer'],
            'contactado' => ['required', 'boolean'],
            'fecha' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return !$this->input('contactado') || !in_array($this->input('motivo'), ['Cita de entrenamiento', 'Cita final', 'Re-entrenamiento']);
                })
            ],
            'hora' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return !$this->input('contactado') || !in_array($this->input('motivo'), ['Cita de entrenamiento', 'Cita final', 'Re-entrenamiento']);
                })
            ],
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
                'date_format:d/m/Y',
                'required_if:contactado,0'
            ],
            'fecha_cita' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Agendar cita de entrenamiento', 'Agendar cita final']);
                })
            ],
            'lugar_cita' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Agendar cita de entrenamiento', 'Agendar cita final']);
                })
            ],
            'lugar_cita_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('lugar_cita') === 'Otro';
                })
            ],
            'hora_cita' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Agendar cita de entrenamiento', 'Agendar cita final']);
                })
            ],
            'fecha_cita_final' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Cita de entrenamiento', 'Cita final', 'Re-entrenamiento']);
                })
            ],
            'hora_cita_final' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Cita de entrenamiento', 'Cita final', 'Re-entrenamiento']);
                })
            ],
            'seguimiento_pregunta_a' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), ['Seguimiento 2 semanas luego del entrenamiento', 'Seguimiento mensual']);
                })
            ],
            'seguimiento_pregunta_b' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_a') === 'Si';
                })
            ],
            'seguimiento_pregunta_c' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_a') === 'Si';
                })
            ],
            'seguimiento_pregunta_d' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_c') === 'Si';
                })
            ],
            'seguimiento_pregunta_e' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_c') === 'No';
                })
            ],
            'fecha_reentrenamiento' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_e') === 'No';
                })
            ],
            'hora_reentrenamiento' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_e') === 'No';
                })
            ],
            'lugar_reentrenamiento' => [
                'nullable',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_e') === 'No';
                })
            ],
            'razon_contacto_participante' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Contacto iniciado por el participante';
                })
            ],
            'razon_contacto_participante_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('razon_contacto_participante') === 'Otro';
                })
            ],
            'contacto_participante_acciones' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Contacto iniciado por el participante';
                })
            ],
            'no_adherencia_pregunta_a' => [
                'nullable',
                'array',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'No completa las sesiones';
                })
            ],
            'no_adherencia_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array('Otro', $this->input('no_adherencia_pregunta_a', []));
                })
            ],
            'no_adherencia_pregunta_b' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'No completa las sesiones' && !in_array('Ya no quiere participar en el proyecto', $this->input('no_adherencia_pregunta_a', []));
                })
            ],
            'no_adherencia_pregunta_c' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('no_adherencia_pregunta_b') === 'No';
                })
            ],
            'problemas_conexion_pregunta_a' => [
                'nullable',
                'array',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Problemas de conexión';
                })
            ],
            'problemas_conexion_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array('Otro', $this->input('problemas_conexion_pregunta_a', []));
                })
            ],
            'problemas_conexion_pregunta_b' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Problemas de conexión';
                })
            ],
            'problemas_conexion_pregunta_c' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('problemas_conexion_pregunta_b') === 'No';
                })
            ],
            'alerta_clinica_pregunta_a' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Alerta clínica';
                })
            ],
            'alerta_clinica_pregunta_b' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('alerta_clinica_pregunta_a') === 'No';
                })
            ],
            'alerta_clinica_pregunta_c' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('alerta_clinica_pregunta_a') === 'Si';
                })
            ],
            'alerta_clinica_pregunta_d' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('alerta_clinica_pregunta_c') === 'No';
                })
            ],
            'alerta_clinica_pregunta_e' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('alerta_clinica_pregunta_c') === 'Si';
                })
            ],
            'alerta_clinica_pregunta_f' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('alerta_clinica_pregunta_e') === 'No';
                })
            ],
            'pedido_ayuda_pregunta_a' => [
                'nullable',
                'array',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Pedido de ayuda';
                })
            ],
            'pedido_ayuda_pregunta_b' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Pedido de ayuda' && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_c' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === 'Pedido de ayuda' && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_d' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('pedido_ayuda_pregunta_c') === 'No';
                })
            ],
            'id_entrenamiento' => ['nullable'],
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
            'date_format' => 'Ingrese una fecha válida en formato DD/MM/AAAA.',
            'in' => 'El valor seleccionado no es válido.',

            'hora.date_format' => 'Ingrese una hora válida en formato HH:MM.',
            'hora_cita_final.date_format' => 'Ingrese una hora válida en formato HH:MM.',
        ];
    }
}
