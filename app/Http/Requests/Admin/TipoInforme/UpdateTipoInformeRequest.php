<?php

namespace App\Http\Requests\Admin\TipoInforme;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoInformeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced by route middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'structure' => ['sometimes', 'array'],
        ];
    }
}
