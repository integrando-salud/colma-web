<?php

namespace App\Http\Requests\Dashboard\Interacciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteInteraccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isA('superusuario') || auth()->user()->isA('supervisor_pais'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'interaccion' => ['required', 'exists:App\Models\Interaccion,id'],
        ];
    }
}
