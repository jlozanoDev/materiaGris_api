# report-transcribe Specification

## Requirements

### Requirement: Authorization

MUST require JWT auth and `report.edit` permission.

| Scenario | Code |
|----------|------|
| Valid JWT with `report.edit` → request proceeds | 200 |
| No JWT → "No autenticado" | 401 |
| No `report.edit` → "No tienes permisos" | 403 |

### Requirement: Input Validation

MUST validate: audio file present, supported format (webm/mp3/wav/ogg/m4a/flac/mp4), ≤25MB. `diarization` (required bool). `language` (optional ISO 639-1). Report `{id}` MUST exist.

| Scenario | Code |
|----------|------|
| Valid file + `diarization: true` + `language: "es"` → passes | — |
| Audio missing | 422 |
| Unsupported MIME → "Formato de audio no soportado" | 415 |
| File >25MB → "El archivo de audio excede el tamaño máximo" | 413 |
| Report not found → "Informe no encontrado" | 404 |

### Requirement: Audio Transcription via MiMo

MUST send audio as base64 in user message to MiMo-V2.5 at `POST https://opencode.ai/zen/go/v1/chat/completions`. Fixed system prompt SHALL instruct transcription with diarization. Response parsed as structured JSON.

#### Scenario: Successful transcription with diarization

- GIVEN valid audio and `diarization: true`
- WHEN MiMo processes chat completions request
- THEN response includes `transcript` (string), `segments` (array of {speaker, text, start, end}), `language` (ISO 639-1), `duration_seconds` (float)

#### Scenario: Language auto-detection

- GIVEN `language` omitted
- THEN `language` reflects MiMo-detected language

### Requirement: Speaker Diarization

MUST return segments with `speaker` in "Speaker N" format when `diarization: true`. When false, single segment with `speaker: "Speaker 1"`.

#### Scenario: Diarization enabled — multiple speakers

- GIVEN `diarization: true` and multi-speaker audio
- THEN segments contain distinct "Speaker 1", "Speaker 2", etc.

#### Scenario: Diarization disabled — single speaker

- GIVEN `diarization: false`
- THEN single segment with `speaker: "Speaker 1"`

### Requirement: Response Envelope

MUST wrap all 200 responses in `{ data, meta, message }`.

#### Scenario: Success envelope

- GIVEN successful transcription
- THEN `{ data: { transcript, segments, language, duration_seconds }, meta: {}, message: "Transcripción completada" }`

### Requirement: Error Handling

MUST timeout at 120s, retry once on malformed JSON, map exceptions to HTTP codes per error mapping table in the change proposal.

#### Scenario: Timeout
- GIVEN MiMo no response within 120s → 500 "Error al procesar el audio"

#### Scenario: Malformed JSON retry
- GIVEN 1st response invalid JSON → retry once; if valid → 200. If still invalid → 500

#### Scenario: Service unavailable
- GIVEN MiMo unreachable → 503 "Servicio de transcripción temporalmente no disponible"

### Requirement: Prompt Injection Protection

MUST separate system/user messages. Audio SHALL be in user message only. System prompt MUST be fixed.

#### Scenario: Message isolation
- GIVEN any audio content → audio placed in user message only; system prompt is fixed instruction

### Requirement: LlmInteraction Persistence

MUST persist `LlmInteraction` with `type='stt'` on every MiMo call — success AND failure.

#### Scenario: Success persistence
- GIVEN successful transcription → `LlmInteraction` saved with `type='stt'`, full payloads

#### Scenario: Failure persistence
- GIVEN failed/timed-out call → `LlmInteraction` saved with `type='stt'`, request payload and error in response_payload

### Requirement: PII/PHI Safety

MUST NOT log audio content or transcript text. Logs SHALL contain only: `report_id`, `duration_seconds`, `language`, `audio_size_bytes`.

#### Scenario: Log safety
- GIVEN transcription with clinical content → logs contain only metadata; NO transcript/audio content

### Requirement: Extra Fields Discarding

MUST discard MiMo response fields not in expected schema (`transcript`, `segments`, `language`, `duration_seconds`).

#### Scenario: Unexpected fields
- GIVEN MiMo returns extra fields → excluded from response `data`
