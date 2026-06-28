# Design: Transcripción de audio médico con IA (MiMo)

## Technical Approach

Hexagonal architecture mirroring `report-extract-data`: `TranscribeReportAction` → `TranscribeReportRequest` → `TranscribeReportCommand` → `SpeechToTextService` + repositories. Synchronous HTTP call to MiMo-V2.5 via Laravel `Http` facade, 120s timeout, 1 retry on parse failure. Response wrapped in standard `{ data, meta, message }` envelope. First file-upload endpoint in the project.

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| STT transport | Laravel `Http` facade, raw JSON POST to `chat/completions` — audio as base64 in user `content` | `audio/transcriptions` endpoint (doesn't exist on OpenCode), separate STT SDK | OpenCode.ai Zen is chat-completions-only for MiMo-V2.5. Matches `LlmExtractorService` pattern exactly. |
| Audio handling | Multipart → base64 in-memory → discard after call. NO disk storage. | `Storage::put()`, temp files | PII/PHI safety: audio never hits disk. Laravel `UploadedFile::get()` + `base64_encode()` — 0 lines of storage code. |
| Diarization | Prompt-based via MiMo (system prompt instructs speaker identification) | Separate diarization service, WhisperX | MiMo-V2.5 handles multimodal natively. Single round-trip. "Speaker N" format is simple and frontend-ready. |
| Response envelope | `{ data: { transcript, segments, language, duration_seconds }, meta: {}, message: "Transcripción completada" }` | Raw transcript, flat `{transcript, ...}` | Matches project convention (`ListReportsAction`, `ExtractReportDataAction`). Frontend expects `{ data, meta, message }`. |
| Config storage | Dedicated `config/stt.php` (env-backed) | `config/services.php` section, inline in service | Mirrors `config/llm.php` pattern. Clean separation; STT is a distinct domain. |
| LlmInteraction reuse | Add `type` column (values: `extraction`, `stt`) to existing `llm_interactions` table | New `stt_interactions` table | Single audit table for all AI calls. Non-destructive migration (nullable column, no FK changes). |
| MiMo prompt strategy | Fixed system prompt (transcription instructions) + user message with base64 audio + text instruction for JSON format | All in system prompt, all in user message | OpenCode requires audio as `content` part in user message. System prompt = pure instruction; user message = data. |
| Retry strategy | 1 retry on JSON parse failure only | Multiple retries, retry on timeout | Single retry covers transient MiMo misbehavior. Timeout retries waste 120s each — better to fail fast. |
| DI in Service constructor | `SpeechToTextService` reads `config('stt')` in constructor | Pass config per-method, use container binding | Mirrors `LlmExtractorService` pattern: constructor receives `array $config`. Simpler DI, testable. |

## Data Flow

```
POST /api/reports/{id}/transcribe  (multipart/form-data: audio file + diarization + language)
    │
    ▼
TranscribeReportAction::__invoke(TranscribeReportRequest $req, int $id)
    │  validates: audio file (required, mimes, max:25000), diarization (required, boolean),
    │            language (optional, string, size:2)
    │
    ▼
TranscribeReportCommand::execute($id, UploadedFile $audio, bool $diarization, ?string $language, User $user)
    │
    ├── PermissionService::ensure($user, 'report.edit')
    ├── PatientReportReadRepository::buscarPorId($id) → PatientReport | null
    │     └── null → throw ModelNotFoundException (→ 404 in Action)
    │
    ├── SpeechToTextService::transcribe($options)
    │     │  $options: [audio (UploadedFile), diarization (bool), language (?string)]
    │     │
    │     ├── encodeAudioBase64($audio) → string (base64)
    │     ├── audioSizeBytes = $audio->getSize()
    │     │
    │     ├── buildSystemPrompt($diarization, $language) → string
    │     │     └── Fixed system prompt: "Eres un transcriptor médico...".
    │     │         Includes diarization/language instructions if set.
    │     │
    │     ├── buildUserMessage($base64Audio, $diarization, $language) → array (content parts)
    │     │     └── [{type: "text", text: "Transcribe el siguiente audio..."},
    │     │          {type: "input_audio", input_audio: {data: "<base64>", format: "wav"}}]
    │     │
    │     ├── buildRequestPayload($systemPrompt, $userContent) → array
    │     │     └── {model, messages: [{role: "system", content: $systemPrompt},
    │     │                             {role: "user", content: $userContent}],
    │     │         response_format: {type: "json_object"}, temperature: 0.1}
    │     │
    │     ├── Http::withToken(config('stt.api_key'))
    │     │     .timeout(config('stt.timeout', 120))
    │     │     .post(config('stt.base_url') . '/chat/completions', $payload)
    │     │     └── throws on timeout/5xx → SttTimeoutException / SttUnavailableException
    │     │
    │     ├── extractAssistantContent($httpBody) → string (JSON)
    │     ├── parseTranscriptionResponse($jsonString) → array
    │     │     └── Validates: transcript (string), segments (array),
    │     │         language (string), duration_seconds (float)
    │     │         Discards extra fields.
    │     │     └── on parse failure → LlmResponseException → retry once
    │     │
    │     └── return [transcript, segments, language, duration_seconds,
    │                 audio_size_bytes, processing_time_ms]
    │
    ├── Save LlmInteraction (type='stt'):
    │     patient_report_id, request_payload (metadata only), response_payload,
    │     processing_time_ms
    │     └── Saved on BOTH success and failure (error info in response_payload)
    │
    └── return [transcript, segments, language, duration_seconds]
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Actions/Reports/TranscribeReportAction.php` | Create | Invocable action. Delegates to command, maps exceptions to HTTP codes (see Error Mapping). |
| `app/Http/Requests/Reports/TranscribeReportRequest.php` | Create | Validates `audio` (required, file, mimes, max:25000), `diarization` (required, boolean), `language` (optional, string, size:2). |
| `app/Commands/Reports/TranscribeReportCommand.php` | Create | Use case: permission check, report existence, STT delegation, LlmInteraction persistence. |
| `app/Services/SpeechToTextService.php` | Create | Base64 encoding, prompt building, HTTP call, JSON parsing with 1 retry. PII-safe logging. |
| `app/DTOs/TranscribeReportResult.php` | Create | Immutable DTO: `transcript`, `segments`, `language`, `duration_seconds`. |
| `app/Exceptions/SttTimeoutException.php` | Create | Thrown when STT HTTP call exceeds 120s timeout. |
| `app/Exceptions/SttUnavailableException.php` | Create | Thrown when provider returns 503 or connection refused. |
| `app/Exceptions/SttResponseException.php` | Create | Thrown when STT response JSON is malformed (after retry). |
| `config/stt.php` | Create | STT config from env: `provider`, `api_key`, `model`, `base_url`, `timeout`. Mirrors `config/llm.php`. |
| `database/migrations/{timestamp}_add_type_to_llm_interactions.php` | Create | Adds `type` column (varchar, default `extraction`) to `llm_interactions`. |
| `tests/Feature/Actions/Reports/TranscribeReportTest.php` | Create | Feature tests: happy path, auth, permissions, validation, timeout, formats, persistence. |
| `routes/api.php` | Modify | Add `POST /reports/{id}/transcribe → TranscribeReportAction` with `auth.jwt` + `require_permissions:report.edit`. |
| `app/Models/LlmInteraction.php` | Modify | Add `type` to `$fillable` and `$casts`. |
| `.env.example` | Modify | Add `STT_PROVIDER`, `STT_API_KEY`, `STT_MODEL`, `STT_BASE_URL`, `STT_TIMEOUT` entries. |
| `.env` | Modify | Add same variables with development values. |

## Interfaces / Contracts

### SpeechToTextService::transcribe signature

```php
/**
 * Transcribe audio using MiMo-V2.5 via OpenCode.ai chat completions.
 *
 * @param array $options {
 *     audio: \Illuminate\Http\UploadedFile (required),
 *     diarization: bool (required),
 *     language: ?string (optional ISO 639-1),
 * }
 * @return array{transcript: string, segments: array<int, array{speaker: string, text: string, start: float, end: float}>, language: string, duration_seconds: float, audio_size_bytes: int, processing_time_ms: int}
 *
 * @throws SttTimeoutException
 * @throws SttResponseException
 * @throws SttUnavailableException
 */
public function transcribe(array $options): array
```

### TranscribeReportCommand::execute signature

```php
/**
 * Execute the transcribe report use case.
 *
 * @param int              $reportId    The patient report ID
 * @param \Illuminate\Http\UploadedFile $audio  The uploaded audio file
 * @param bool             $diarization Whether to enable speaker diarization
 * @param string|null      $language    Optional ISO 639-1 language code
 * @param \App\Models\User $user        The authenticated user
 * @return array{transcript: string, segments: array, language: string, duration_seconds: float}
 *
 * @throws \App\Exceptions\PermissionDeniedException
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
 */
public function execute(
    int $reportId,
    UploadedFile $audio,
    bool $diarization,
    ?string $language,
    User $user
): array
```

## MiMo Prompt Design

### System Prompt (fixed — zero user data)

```
Eres un transcriptor médico profesional. Tu tarea es transcribir fielmente
el audio de una consulta médica proporcionado por el usuario.

Reglas:
- Transcribe TODO el audio palabra por palabra.
- NO resumas, NO interpretes, NO añadas información que no esté en el audio.
- Si hay múltiples hablantes, identifícalos como "Speaker 1", "Speaker 2", etc.
- Si diarization está desactivado, usa un solo "Speaker 1".
- Devuelve SOLAMENTE JSON válido sin texto adicional.
- El campo "transcript" contiene el texto completo.
- Cada segmento en "segments" incluye speaker, text, start (segundos), end (segundos).
- Detecta el idioma del audio y devuélvelo como código ISO 639-1 en "language".
- Calcula la duración total en segundos como "duration_seconds".
```

### User Message (contains base64 audio + text instruction)

```php
// Content array for the user message:
$content = [
    [
        'type' => 'text',
        'text' => "Transcribe el siguiente audio médico. "
                . ($diarization ? "Identifica y separa los hablantes como 'Speaker N'." : "Usa un solo hablante 'Speaker 1'.")
                . ($language ? " El audio está en idioma '{$language}'." : ""),
    ],
    [
        'type' => 'input_audio',
        'input_audio' => [
            'data' => $base64Audio,
            'format' => $this->mapMimeToFormat($audio->getMimeType()),
        ],
    ],
];
```

### Request Payload

```php
[
    'model' => config('stt.model', 'mimo-v2.5'),
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userContent], // array of content parts
    ],
    'response_format' => ['type' => 'json_object'],
    'temperature' => 0.1,
]
```

### Expected MiMo JSON Response (parsed)

```json
{
    "transcript": "Texto completo de la transcripción...",
    "segments": [
        {"speaker": "Speaker 1", "text": "Texto del hablante 1", "start": 0.0, "end": 5.2},
        {"speaker": "Speaker 2", "text": "Texto del hablante 2", "start": 5.5, "end": 12.8}
    ],
    "language": "es",
    "duration_seconds": 12.8
}
```

## Prompt Injection Protection

1. **System prompt**: Fully fixed server-side in `buildSystemPrompt()` — zero user data. Contains only transcription instructions and format rules.
2. **Audio isolation**: Audio is placed exclusively in `role: "user"` message as `input_audio` content part. Never in system prompt.
3. **No user text in system prompt**: The `language` ISO code (2 chars max, validated by FormRequest `size:2`) and `diarization` (boolean) are the ONLY user inputs — both validated before reaching the prompt builder.
4. **Structured output enforcement**: `response_format: { type: "json_object" }` ensures MiMo returns parseable JSON.
5. **Post-validation**: `parseTranscriptionResponse()` JSON-decodes, verifies required keys (`transcript`, `segments`, `language`, `duration_seconds`), discards all extra fields.
6. **No prompt templates from user**: Unlike extract-data where transcript is user-provided text, the STT path receives a binary audio file — no free-text injection vector.

## Error Mapping

| Exception | HTTP | Message |
|-----------|------|---------|
| `ModelNotFoundException` (report) | 404 | "Informe no encontrado" |
| `PermissionDeniedException` | 403 | "No tienes permisos" |
| `ValidationException` (FormRequest — audio missing) | 422 | Laravel default |
| `ValidationException` (FormRequest — unsupported MIME) | 415 | "Formato de audio no soportado" |
| `ValidationException` (FormRequest — file >25MB) | 413 | "El archivo de audio excede el tamaño máximo" |
| `SttTimeoutException` | 500 | "Error al procesar el audio" |
| `SttResponseException` | 500 | "Error al procesar el audio" |
| `SttUnavailableException` | 503 | "Servicio de transcripción temporalmente no disponible" |

## Testing Strategy

| Layer | Test | Approach |
|-------|------|----------|
| Feature | `TranscribeReportTest` | 12 tests: happy path with diarization (mocked STT → 200, verify envelope), happy path without diarization (single Speaker 1), auth (401), permission (403), audio missing (422), unsupported format (415 → custom Response override), file too large (413), report not found (404), STT timeout (500), malformed JSON after retry (500), STT unavailable (503), LlmInteraction saved with type=stt |
| Unit | `SpeechToTextServiceTest` | 7 tests: base64 encoding preserves MIME type mapping, system prompt includes diarization instruction, system prompt includes language hint, user message content parts structure, parse valid transcription JSON, parse invalid JSON throws SttResponseException, parse missing keys throws SttResponseException |
| Unit | `TranscribeReportCommandTest` | Integration-style: mock STT service, verify permission check, verify report lookup, verify LlmInteraction creation with type=stt, verify metadata-only request_payload (no audio/transcript) |

## Open Questions

- [ ] **MIME-to-format mapping**: Which audio format strings does OpenCode.ai Zen accept in `input_audio.format`? WebM → "webm", MP3 → "mp3", WAV → "wav", OGG → "ogg", M4A → "mp4", FLAC → "flac", MP4 → "mp4"? Needs verification against OpenCode docs.
- [ ] **422/415/413 differentiation**: Laravel FormRequest returns 422 by default for all validation failures. The spec requires 415 for unsupported MIME and 413 for oversized files. Solution: override `failedValidation()` in `TranscribeReportRequest` to inspect `$validator->errors()` and set response status dynamically based on error keys (`audio.mimes` → 415, `audio.max` → 413).
- [ ] **`input_audio` content part format**: Confirming the exact shape OpenCode expects — `{type: "input_audio", input_audio: {data: "<base64>", format: "wav"}}` vs alternative nesting. This mirrors OpenAI's audio input format but needs OpenCode-specific validation.
