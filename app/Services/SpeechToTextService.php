<?php

namespace App\Services;

use App\DTOs\TranscribeResult;
use App\Exceptions\AiResponseException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiUnavailableException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\ConnectException;

class SpeechToTextService
{
    /**
     * @param array<string, mixed> $config STT configuration from config('stt')
     */
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * Transcribe audio using MiMo-V2.5 via OpenCode.ai chat completions.
     *
     * @param string      $audioBase64 Base64-encoded audio data
     * @param string      $audioFormat Audio format (wav, mp3, webm, etc.)
     * @param bool        $diarization Whether to enable speaker diarization
     * @param string|null $language    Optional ISO 639-1 language code
     * @return TranscribeResult
     *
     * @throws AiTimeoutException
     * @throws AiResponseException
     * @throws AiUnavailableException
     */
    public function transcribe(
        string $audioBase64,
        string $audioFormat,
        bool $diarization = true,
        ?string $language = null,
    ): TranscribeResult {
        $startTime = microtime(true);

        $messages = $this->buildMessages($audioBase64, $audioFormat, $diarization, $language);
        $payload = $this->buildRequestPayload($messages);

        try {
            $response = $this->callStt($payload);
        } catch (AiTimeoutException | AiUnavailableException $e) {
            throw $e;
        }

        $assistantContent = $this->extractAssistantContent($response->body());

        try {
            $result = $this->parseTranscriptionResponse($assistantContent);
        } catch (AiResponseException $e) {
            // Retry once on parse failure
            try {
                $response = $this->callStt($payload);
                $assistantContent = $this->extractAssistantContent($response->body());
                $result = $this->parseTranscriptionResponse($assistantContent);
            } catch (AiResponseException $retryException) {
                throw $retryException;
            }
        }

        $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        return new TranscribeResult(
            transcript: $result->transcript,
            segments: $result->segments,
            language: $result->language,
            durationSeconds: $result->durationSeconds,
            processingTimeMs: $processingTimeMs,
        );
    }

    /**
     * Build the system prompt for medical transcription.
     *
     * @param bool        $diarization Whether to enable speaker diarization
     * @param string|null $language    Optional ISO 639-1 language code
     * @return string The system prompt
     */
    public function buildSystemPrompt(bool $diarization = true, ?string $language = null): string
    {
        $lines = [
            'Eres un asistente experto en transcripción médica. Tu tarea es transcribir el audio de una consulta médica con máxima precisión.',
            '',
            'Reglas:',
            '- Devuelve SOLAMENTE JSON válido sin texto adicional.',
            '- Identifica los hablantes como "Speaker 1", "Speaker 2", etc. basándote en cambios de voz y contexto.',
            '- Cada segmento debe incluir el texto transcrito exacto y sus marcas de tiempo (inicio y fin en segundos).',
            '- Detecta el idioma automáticamente y devuélvelo en código ISO 639-1.',
            '- Si el audio no es claro, haz tu mejor esfuerzo y marca los segmentos dudosos en el texto.',
            '- NO añadas interpretaciones ni diagnósticos. Solo transcribe lo que se dice.',
            '',
            'Formato de respuesta requerido:',
            '{',
            '  "transcript": "texto completo",',
            '  "segments": [',
            '    {"speaker": "Speaker 1", "text": "...", "start": 0.0, "end": 3.2}',
            '  ],',
            '  "language": "es",',
            '  "duration_seconds": 15.3',
            '}',
        ];

        if (! $diarization) {
            $lines[] = '';
            $lines[] = 'IMPORTANTE: No intentes identificar hablantes. Agrupa toda la transcripción bajo \'Speaker 1\'.';
        }

        return implode("\n", $lines);
    }

    /**
     * Build the messages array for the chat completions request.
     *
     * @param string      $audioBase64 Base64-encoded audio data
     * @param string      $audioFormat Audio format (wav, mp3, webm, etc.)
     * @param bool        $diarization Whether to enable speaker diarization
     * @param string|null $language    Optional ISO 639-1 language code
     * @return array<int, array<string, mixed>>
     */
    public function buildMessages(
        string $audioBase64,
        string $audioFormat,
        bool $diarization,
        ?string $language,
    ): array {
        $systemPrompt = $this->buildSystemPrompt($diarization, $language);

        $userText = 'Transcribe el siguiente audio médico.'
            . ($diarization ? " Identifica y separa los hablantes como 'Speaker N'." : " Usa un solo hablante 'Speaker 1'.")
            . ($language ? " El audio está en idioma '{$language}'." : '');

        $userContent = [
            [
                'type' => 'text',
                'text' => $userText,
            ],
            [
                'type' => 'input_audio',
                'input_audio' => [
                    'data' => $audioBase64,
                    'format' => $this->mapMimeToFormat($audioFormat),
                ],
            ],
        ];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];
    }

    /**
     * Build the HTTP request payload for an OpenAI-compatible chat completions API.
     *
     * @param array<int, array<string, mixed>> $messages The messages array
     * @return array<string, mixed>
     */
    public function buildRequestPayload(array $messages): array
    {
        return [
            'model' => $this->config['model'] ?? 'mimo-v2.5',
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
        ];
    }

    /**
     * Parse and validate the STT JSON response into a TranscribeResult DTO.
     *
     * @param string $responseBody The assistant's message content (JSON string)
     * @return TranscribeResult
     *
     * @throws AiResponseException If JSON is invalid or required fields are missing
     */
    public function parseTranscriptionResponse(string $responseBody): TranscribeResult
    {
        if (empty($responseBody)) {
            throw new AiResponseException('Empty response from STT service');
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AiResponseException(
                'Invalid JSON response from STT: ' . json_last_error_msg()
            );
        }

        if (! isset($data['transcript'])) {
            throw new AiResponseException('Missing transcript field in STT response');
        }

        return TranscribeResult::fromArray($data);
    }

    /**
     * Map a MIME type to an audio format string accepted by OpenCode.ai.
     *
     * @param string $mimeType The MIME type (e.g. 'audio/wav', 'audio/mpeg')
     * @return string The format string (e.g. 'wav', 'mp3')
     */
    public function mapMimeToFormat(string $mimeType): string
    {
        return match ($mimeType) {
            'audio/webm' => 'webm',
            'audio/wav', 'audio/wave' => 'wav',
            'audio/mp3', 'audio/mpeg' => 'mp3',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'audio/flac' => 'flac',
            default => 'wav',
        };
    }

    /**
     * Make the HTTP call to the STT provider.
     *
     * @param array<string, mixed> $payload The request payload
     * @return Response
     *
     * @throws AiTimeoutException
     * @throws AiUnavailableException
     */
    private function callStt(array $payload): Response
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://opencode.ai/zen/go/v1', '/');
        $timeout = $this->config['timeout'] ?? 120;

        try {
            $response = Http::withToken($this->config['api_key'] ?? '')
                ->timeout($timeout)
                ->post($baseUrl . '/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new AiUnavailableException(
                'STT service unavailable: ' . $e->getMessage()
            );
        } catch (ConnectException $e) {
            throw new AiUnavailableException(
                'STT service unavailable: ' . $e->getMessage()
            );
        }

        if ($response->status() === 503) {
            throw new AiUnavailableException('STT service returned 503 Unavailable');
        }

        if (method_exists($response, 'timedOut') && $response->timedOut()) {
            throw new AiTimeoutException('STT request timed out after ' . $timeout . ' seconds');
        }

        return $response;
    }

    /**
     * Extract the assistant's message content from an OpenAI-compatible response body.
     *
     * @param string $responseBody Raw HTTP response body
     * @return string The assistant's message content (JSON string)
     *
     * @throws AiResponseException
     */
    private function extractAssistantContent(string $responseBody): string
    {
        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AiResponseException('Invalid JSON in STT response wrapper');
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if ($content === null) {
            throw new AiResponseException('No content in STT response');
        }

        return $content;
    }
}
