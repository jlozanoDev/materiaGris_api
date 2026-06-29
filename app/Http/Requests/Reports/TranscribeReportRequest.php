<?php

namespace App\Http\Requests\Reports;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class TranscribeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by middleware (auth.jwt + require_permissions)
    }

    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:webm,wav,mp3,mp4,ogg,m4a,flac', 'max:25600'],
            'diarization' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'El archivo de audio es obligatorio',
            'audio.file' => 'El audio debe ser un archivo válido',
            'audio.mimes' => 'Formato de audio no soportado. Formatos aceptados: webm, wav, mp3, mp4, ogg, m4a, flac',
            'audio.max' => 'El archivo de audio excede el tamaño máximo permitido de 25MB',
            'diarization.boolean' => 'diarization debe ser verdadero o falso',
            'language.string' => 'El idioma debe ser una cadena de texto',
            'language.size' => 'El idioma debe ser un código ISO 639-1 de 2 caracteres',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        // Unsupported audio format → 415
        if (isset($failed['audio']['Mimes'])) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Formato de audio no soportado',
                    'errors' => $validator->errors(),
                ], 415)
            );
        }

        // Audio exceeds max file size → 413
        if (isset($failed['audio']['Max'])) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'El archivo de audio excede el tamaño máximo',
                    'errors' => $validator->errors(),
                ], 413)
            );
        }

        // Default validation error → 422
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
