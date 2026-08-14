<?php

namespace App\Http\Requests\API\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'rol' => ['nullable', 'exists:roles,name'],
            'pais' => ['nullable', 'exists:paises,id'],
            'nombre' => ['nullable'],
            'orderBy' => ['nullable'],
            'orderDir' => ['nullable', 'in:asc,desc'],
        ];
    }
}
