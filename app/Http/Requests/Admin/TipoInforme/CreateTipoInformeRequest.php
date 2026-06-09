<?php

namespace App\Http\Requests\Admin\TipoInforme;

use Illuminate\Foundation\Http\FormRequest;

class CreateTipoInformeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced by route middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'structure' => ['required', 'array'],
        ];
    }
}
