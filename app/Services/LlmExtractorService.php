<?php

namespace App\Services;

use App\Exceptions\LlmResponseException;
use App\Exceptions\LlmTimeoutException;
use App\Exceptions\LlmUnavailableException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LlmExtractorService
{
    /**
     * @param array<string, mixed> $config LLM configuration from config('llm')
     */
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * Orchestrate the full extraction flow.
     *
     * @param array  $templateStructure The template structure snapshot (sections → rows → columns → fields)
     * @param string $transcript        Raw transcription text
     * @param array  $patientContext    Patient context with edad, sexo, medicacion, last_reports
     * @return array{extracted_data: array, confidence_scores: array, warnings: array, processing_time_ms: int}
     *
     * @throws LlmTimeoutException
     * @throws LlmResponseException
     * @throws LlmUnavailableException
     */
    public function extract(array $templateStructure, string $transcript, array $patientContext): array
    {
        $startTime = microtime(true);

        $flattenedFields = $this->flattenFields($templateStructure);
        $fieldKeys = array_column($flattenedFields, 'field');

        $systemPrompt = $this->buildSystemPrompt($templateStructure);
        $sanitizedTranscript = $this->sanitizeTranscript($transcript);
        $userMessage = $this->buildUserMessage($sanitizedTranscript, $patientContext);
        $payload = $this->buildRequestPayload($systemPrompt, $userMessage);

        try {
            $response = $this->callLlm($payload);
        } catch (LlmTimeoutException $e) {
            throw $e;
        } catch (LlmUnavailableException $e) {
            throw $e;
        }

        $assistantContent = $this->extractAssistantContent($response->body());

        try {
            $parsed = $this->parseLlmResponse($assistantContent, $fieldKeys);
        } catch (LlmResponseException $e) {
            // Retry once on parse failure
            try {
                $response = $this->callLlm($payload);
                $assistantContent = $this->extractAssistantContent($response->body());
                $parsed = $this->parseLlmResponse($assistantContent, $fieldKeys);
            } catch (LlmResponseException $retryException) {
                throw $retryException;
            }
        }

        $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        $parsed['processing_time_ms'] = $processingTimeMs;

        return $parsed;
    }

    /**
     * Build the system prompt from template fields.
     *
     * @param array $templateStructure Nested template structure (sections → rows → columns → fields)
     * @return string The system prompt
     */
    public function buildSystemPrompt(array $templateStructure): string
    {
        $fields = $this->flattenFields($templateStructure);

        $lines = [
            'Eres un asistente experto en medicina especializado en extraer datos clínicos de transcripciones de consultas médicas.',
            '',
            'Reglas:',
            '- Devuelve SOLAMENTE JSON válido sin texto adicional.',
            '- Usa null para campos sin información en la transcripción.',
            '- No inventes datos que no estén presentes en la transcripción.',
            '- Si un campo no tiene información, devuélvelo como null.',
            '',
            'Campos del template:',
        ];

        foreach ($fields as $field) {
            $description = $field['ai_help_description'] ?? $field['label'];
            $lines[] = sprintf(
                '{ field: "%s", label: "%s", type: "%s", ai_help_description: "%s" }',
                $field['field'],
                $field['label'],
                $field['type'],
                $description,
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Sanitize a transcript by removing code fences, delimiters, HTML tags, and normalizing whitespace.
     *
     * @param string $transcript Raw transcript text
     * @return string Sanitized transcript
     */
    public function sanitizeTranscript(string $transcript): string
    {
        // Strip markdown code fences (```, ~~~, ``)
        $text = preg_replace('/```/', '', $transcript);
        $text = preg_replace('/~~~/', '', $text);
        $text = preg_replace('/``/', '', $text);

        // Strip horizontal rule delimiters (---, ***, ===)
        $text = preg_replace('/---/', '', $text);
        $text = preg_replace('/\*\*\*/', '', $text);
        $text = preg_replace('/===/', '', $text);

        // Strip HTML/XML tags (angle brackets and their content)
        $text = strip_tags($text);

        // Normalize multiple spaces to single space
        $text = preg_replace('/[ ]+/', ' ', $text);

        // Normalize multiple newlines to double newline
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Build the user message combining patient context and transcript.
     *
     * @param string $transcript     Sanitized transcript
     * @param array  $patientContext Patient context with edad, sexo, medicacion, last_reports
     * @return string The user message
     */
    public function buildUserMessage(string $transcript, array $patientContext): string
    {
        $medication = ! empty($patientContext['medicacion'])
            ? $patientContext['medicacion']
            : 'No reportada';

        $lines = [
            'DATOS DEL PACIENTE:',
            '- Edad: ' . ($patientContext['edad'] ?? 'No especificada'),
            '- Sexo: ' . ($patientContext['sexo'] ?? 'No especificado'),
            '- Medicación reportada: ' . $medication,
        ];

        if (! empty($patientContext['last_reports'])) {
            $lines[] = '- Historial clínico reciente:';
            foreach ($patientContext['last_reports'] as $report) {
                if (is_array($report)) {
                    $lines[] = '  ' . json_encode($report, JSON_UNESCAPED_UNICODE);
                } else {
                    $lines[] = '  ' . (string) $report;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'TRANSCRIPCIÓN:';
        $lines[] = $transcript;

        return implode("\n", $lines);
    }

    /**
     * Parse and validate the LLM JSON response.
     *
     * @param string $responseBody    The assistant's message content (JSON string)
     * @param array  $templateFieldKeys List of expected field keys from the template
     * @return array{extracted_data: array, confidence_scores: array, warnings: array}
     *
     * @throws LlmResponseException If JSON is invalid or empty
     */
    public function parseLlmResponse(string $responseBody, array $templateFieldKeys): array
    {
        if (empty($responseBody)) {
            throw new LlmResponseException('Empty response from LLM');
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LlmResponseException(
                'Invalid JSON response from LLM: ' . json_last_error_msg()
            );
        }

        $extractedData = [];
        $confidenceScores = [];
        $warnings = [];

        foreach ($templateFieldKeys as $key) {
            $value = $data[$key] ?? null;
            $extractedData[$key] = $value;

            if ($value === null) {
                $warnings[] = "El campo '{$key}' no contiene datos en la transcripción.";
                $confidenceScores[$key] = 0.0;
            } else {
                $confidenceScores[$key] = 1.0;
            }
        }

        return [
            'extracted_data' => $extractedData,
            'confidence_scores' => $confidenceScores,
            'warnings' => $warnings,
        ];
    }

    /**
     * Build the HTTP request payload for an OpenAI-compatible API.
     *
     * @param string $systemPrompt The system prompt
     * @param string $userMessage  The user message
     * @return array<string, mixed>
     */
    public function buildRequestPayload(string $systemPrompt, string $userMessage): array
    {
        return [
            'model' => $this->config['model'] ?? 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
        ];
    }

    /**
     * Make the HTTP call to the LLM provider.
     *
     * @param array $payload The request payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws LlmTimeoutException
     * @throws LlmUnavailableException
     */
    public function callLlm(array $payload): Response
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');
        $timeout = $this->config['timeout'] ?? 30;

        try {
            $response = Http::withToken($this->config['api_key'] ?? '')
                ->timeout($timeout)
                ->post($baseUrl . '/chat/completions', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new LlmUnavailableException(
                'LLM service unavailable: ' . $e->getMessage()
            );
        }

        if ($response->status() === 503) {
            throw new LlmUnavailableException('LLM service returned 503 Unavailable');
        }

        if ($response->timedOut()) {
            throw new LlmTimeoutException('LLM request timed out after ' . $timeout . ' seconds');
        }

        return $response;
    }

    /**
     * Flatten the nested template structure into a flat array of fields.
     *
     * @param array $templateStructure Nested structure (sections → rows → columns → fields)
     * @return array<int, array{field: string, label: string, type: string, ai_help_description: string|null}>
     */
    private function flattenFields(array $templateStructure): array
    {
        $fields = [];

        foreach ($templateStructure['sections'] ?? [] as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                foreach ($row['columns'] ?? [] as $column) {
                    // Some structures may have fields directly under columns
                    if (isset($column['field'])) {
                        $fields[] = [
                            'field' => $column['field'],
                            'label' => $column['label'] ?? $column['field'],
                            'type' => $column['type'] ?? 'text',
                            'ai_help_description' => $column['ai_help_description'] ?? null,
                        ];
                    }

                    // Handle nested fields inside a column
                    if (isset($column['fields']) && is_array($column['fields'])) {
                        foreach ($column['fields'] as $field) {
                            $fields[] = [
                                'field' => $field['field'],
                                'label' => $field['label'] ?? $field['field'],
                                'type' => $field['type'] ?? 'text',
                                'ai_help_description' => $field['ai_help_description'] ?? null,
                            ];
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Extract the assistant's message content from an OpenAI-compatible response body.
     *
     * @param string $responseBody Raw HTTP response body
     * @return string The assistant's message content (JSON string)
     *
     * @throws LlmResponseException
     */
    private function extractAssistantContent(string $responseBody): string
    {
        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LlmResponseException('Invalid JSON in LLM response wrapper');
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if ($content === null) {
            throw new LlmResponseException('No content in LLM response');
        }

        return $content;
    }
}
