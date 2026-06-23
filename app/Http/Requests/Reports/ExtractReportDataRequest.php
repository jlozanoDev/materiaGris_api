<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ExtractReportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by middleware (auth.jwt + require_permissions)
    }

    public function rules(): array
    {
        return [
            'transcript' => ['required', 'string', 'min:1'],
            'template_id' => ['required', 'integer', 'exists:report_templates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'transcript.required' => 'La transcripción no puede estar vacía',
            'transcript.min' => 'La transcripción no puede estar vacía',
            'template_id.required' => 'template_id es requerido',
            'template_id.exists' => 'Plantilla no válida',
        ];
    }
}
