<?php

namespace App\Http\Requests\Dashboard\Interacciones;

use App\Enums\MotivoInteraccion;
use App\Enums\MotivoReprogramacion;
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
            'alerta_id' => ['nullable', 'integer', 'exists:alertas,id'],
            'contactado' => ['required', 'boolean'],
            'fecha' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return !$this->input('contactado') || !in_array($this->input('motivo'), [MotivoInteraccion::CITA_ENTRENAMIENTO->value, MotivoInteraccion::CITA_FINAL->value, MotivoInteraccion::REENTRENAMIENTO->value]);
                })
            ],
            'hora' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return !$this->input('contactado') || !in_array($this->input('motivo'), [MotivoInteraccion::CITA_ENTRENAMIENTO->value, MotivoInteraccion::CITA_FINAL->value, MotivoInteraccion::REENTRENAMIENTO->value]);
                })
            ],
            'modalidad' => [
                'required',
                'in:' . implode(',', Interaccion::MODALIDADES)
            ],
            'motivo' => [
                'required',
                Rule::enum(MotivoInteraccion::class),
            ],
            'observaciones' => [
                'nullable',
                'required_if:alerta_clinica_pregunta_b,No'
            ],
            'motivo_reprogramacion' => [
                'nullable',
                Rule::enum(MotivoReprogramacion::class),
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
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value, MotivoInteraccion::AGENDAR_CITA_FINAL->value]);
                })
            ],
            'lugar_cita' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value, MotivoInteraccion::AGENDAR_CITA_FINAL->value]);
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
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO->value, MotivoInteraccion::AGENDAR_CITA_FINAL->value]);
                })
            ],
            'fecha_cita_final' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::CITA_ENTRENAMIENTO->value, MotivoInteraccion::CITA_FINAL->value, MotivoInteraccion::REENTRENAMIENTO->value]);
                })
            ],
            'hora_cita_final' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::CITA_ENTRENAMIENTO->value, MotivoInteraccion::CITA_FINAL->value, MotivoInteraccion::REENTRENAMIENTO->value]);
                })
            ],
            'seguimiento_pregunta_a' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && in_array($this->input('motivo'), [MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS->value, MotivoInteraccion::SEGUIMIENTO_MENSUAL->value]);
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
                'in:Centro de salud,Vivienda del participante,Otro',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('seguimiento_pregunta_e') === 'No';
                })
            ],
            'lugar_reentrenamiento_otro' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('lugar_reentrenamiento') === 'Otro';
                })
            ],
            'razon_contacto_participante' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::CONTACTO_INICIADO_POR_PARTICIPANTE->value;
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::CONTACTO_INICIADO_POR_PARTICIPANTE->value;
                })
            ],
            'no_adherencia_pregunta_a' => [
                'nullable',
                'array',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::NO_COMPLETA_SESIONES->value;
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::NO_COMPLETA_SESIONES->value && !in_array('Ya no quiere participar en el proyecto', $this->input('no_adherencia_pregunta_a', []));
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::PROBLEMAS_CONEXION->value;
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::PROBLEMAS_CONEXION->value;
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::ALERTA_CLINICA->value;
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
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::PEDIDO_AYUDA->value;
                })
            ],
            'pedido_ayuda_pregunta_b' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::PEDIDO_AYUDA->value && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_c' => [
                'nullable',
                'in:Si,No',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('motivo') === MotivoInteraccion::PEDIDO_AYUDA->value && !in_array('Ya no quiere participar en el proyecto', $this->input('pedido_ayuda_pregunta_a', []));
                })
            ],
            'pedido_ayuda_pregunta_d' => [
                'nullable',
                'date_format:d/m/Y',
                Rule::requiredIf(function () {
                    return $this->input('contactado') && $this->input('pedido_ayuda_pregunta_c') === 'No';
                })
            ],
            'id_entrenamiento' => ['nullable', 'integer', 'exists:entrenamientos,id'],
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
