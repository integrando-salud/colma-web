<?php

namespace App\Http\Requests\API\Paciente;

use App\Enums\AdvertenciaClinica;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->id == $this->input('userId');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'userId' => ['required', 'numeric', 'exists:App\Models\User,id'],

            'glucosa' => ['nullable', 'array'],
            'glucosa.*.id_glucosa' => ['required', 'numeric'],
            'glucosa.*.id_usuario' => ['required', 'numeric', 'same:userId'],
            'glucosa.*.valor_mgdl' => ['required', 'numeric'],
            'glucosa.*.ayuno' => ['required', 'boolean'],
            'glucosa.*.fecha_registro' => ['required', 'date_format:Y-m-d H:i:s'],
            'glucosa.*.fecha_usuario' => ['required', 'date_format:Y-m-d H:i:s'],
            'glucosa.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],
            'glucosa.*.advertencia' => ['required', Rule::enum(AdvertenciaClinica::class)],

            'presion' => ['nullable', 'array'],
            'presion.*.id_presion' => ['required', 'numeric'],
            'presion.*.id_usuario' => ['required', 'numeric', 'same:userId'],
            'presion.*.sistolica' => ['required', 'numeric'],
            'presion.*.diastolica' => ['required', 'numeric'],
            'presion.*.fecha_registro' => ['required', 'date_format:Y-m-d H:i:s'],
            'presion.*.fecha_usuario' => ['required', 'date_format:Y-m-d H:i:s'],
            'presion.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],
            'presion.*.advertencia' => ['required', Rule::enum(AdvertenciaClinica::class)],

            'estado_animo' => ['nullable', 'array'],
            'estado_animo.*.id_estado_animo' => ['required', 'numeric'],
            'estado_animo.*.id_usuario' => ['required', 'numeric', 'same:userId'],
            'estado_animo.*.pregunta_1' => ['required', 'numeric'],
            'estado_animo.*.pregunta_2' => ['required', 'numeric'],
            'estado_animo.*.pregunta_3' => ['required', 'numeric'],
            'estado_animo.*.pregunta_4' => ['required', 'numeric'],
            'estado_animo.*.pregunta_5' => ['required', 'numeric'],
            'estado_animo.*.pregunta_6' => ['required', 'numeric'],
            'estado_animo.*.pregunta_7' => ['required', 'numeric'],
            'estado_animo.*.pregunta_8' => ['required', 'numeric'],
            'estado_animo.*.pregunta_9' => ['required', 'numeric'],
            'estado_animo.*.fecha_registro' => ['required', 'date_format:Y-m-d H:i:s'],
            'estado_animo.*.fecha_usuario' => ['required', 'date_format:Y-m-d H:i:s'],
            'estado_animo.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],

            'quiz' => ['nullable', 'array'],
            'quiz.*.tratamiento_elemento_id' => ['required', 'integer', 'exists:tratamiento_elementos,id'],
            'quiz.*.fecha_registro' => ['required', 'date_format:Y-m-d H:i:s'],
            'quiz.*.fecha_usuario' => ['required', 'date_format:Y-m-d H:i:s'],
            'quiz.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],
            'quiz.*.preguntas' => ['required', 'array'],
            'quiz.*.preguntas.*.pregunta_index' => ['required', 'numeric'],
            'quiz.*.preguntas.*.pregunta_texto' => ['required', 'string'],
            'quiz.*.preguntas.*.respuesta_usuario' => ['required', 'numeric'],
            'quiz.*.preguntas.*.respuesta_usuario_texto' => ['required', 'string'],
            'quiz.*.preguntas.*.respuesta_correcta' => ['required', 'numeric'],
            'quiz.*.preguntas.*.respuesta_correcta_texto' => ['required', 'string'],
            'quiz.*.preguntas.*.es_correcta' => ['required', 'boolean'],
            'quiz.*.preguntas.*.posibles_respuestas' => ['required', 'array'],

            'metas' => ['nullable', 'array'],
            'metas.*.id_meta' => ['required', 'numeric'],
            'metas.*.tratamiento_elemento_id' => ['required', 'integer', 'exists:tratamiento_elementos,id'],
            'metas.*.tipo_actividad' => ['required', 'string'],
            'metas.*.actividad' => ['required', 'string'],
            'metas.*.lugar' => ['nullable', 'string'],
            'metas.*.acompaniante' => ['nullable', 'string'],
            'metas.*.meta_cumplida' => ['required', 'boolean'],
            'metas.*.motivo_incumplimiento' => ['nullable', 'string'],
            'metas.*.fecha_registro' => ['required', 'date_format:Y-m-d H:i:s'],
            'metas.*.fecha_usuario' => ['required', 'date_format:Y-m-d H:i:s'],
            'metas.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],
            'metas.*.fecha_edicion' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'metas.*.fecha_objetivo' => ['required', 'date_format:Y-m-d H:i:s'],

            'progreso_elementos' => ['nullable', 'array'],
            'progreso_elementos.*.tratamiento_elemento_id' => ['required', 'integer', 'exists:tratamiento_elementos,id'],
            'progreso_elementos.*.completado' => ['required', 'boolean'],
            'progreso_elementos.*.fecha_estado' => ['required', 'date_format:Y-m-d H:i:s'],
            'progreso_elementos.*.fecha_sincronizacion' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
