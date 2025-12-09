<?php

namespace App\Http\Requests\Dashboard\User;

use Illuminate\Foundation\Http\FormRequest;

class FilterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('ver-usuarios');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
