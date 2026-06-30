# Módulo de Dictado y Autocompletado de Informes — Documentación Técnica

> **Módulo**: Reports — Transcripción de audio y extracción de datos clínicos con IA
> **Documentación funcional**: [`docs/funcional/modulos/dictado-autocompletado.md`](../../../funcional/modulos/dictado-autocompletado.md)
> **Flujo de API**: [`docs/funcional/flujos/dictado-autocompletado.md`](../../../funcional/flujos/dictado-autocompletado.md)

## Rutas

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| POST | `/api/reports/{id}/extract-data` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `ExtractReportDataAction` |
| POST | `/api/reports/{id}/transcribe` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `TranscribeReportAction` |

## Actions

### `ExtractReportDataAction`
- Action invocable que orquesta `ExtractReportDataRequest` → `ExtractReportDataCommand`.
- Recibe `transcript` (string) y `template_id` (int) del request body.
- Mapea excepciones a códigos HTTP según tabla de error mapping.
- Responde con `{ data: { extracted_data, confidence_scores, warnings, processing_time_ms }, meta: {}, message }`.

### `TranscribeReportAction`
- Action invocable que orquesta `TranscribeReportRequest` → `TranscribeReportCommand`.
- Recibe `audio` (UploadedFile), `diarization` (bool, default true) y `language` (string opcional).
- **Response 200:** `{ data: { transcript, segments[], language, duration_seconds }, meta: {}, message }`.
- **403:** Sin permiso `report.edit`.
- **404:** Informe no encontrado.
- **500:** Error del servicio STT (timeout, respuesta inválida).
- **503:** Servicio STT no disponible.

## Requests

### `ExtractReportDataRequest`
- `transcript`: required, string, min:1 — texto de la transcripción.
- `template_id`: required, integer, exists:report_templates,id — ID de plantilla activa.

### `TranscribeReportRequest`
- `audio`: required, file — archivo de audio. Formatos aceptados: `mp3`, `wav`, `webm`, `ogg`, `m4a`, `flac`. Máx. 25 MB.
- `diarization`: boolean, opcional, default `true` — si el backend debe identificar hablantes.
- `language`: string, opcional — código ISO 639-1 del idioma (ej. `es`, `en`). Si se omite, autodetecta.

## Commands (Use Cases)

### `ExtractReportDataCommand`
- Verifica permiso `report.edit` vía `PermissionService::ensure()`.
- Busca `PatientReport::findOrFail($reportId)`.
- Valida template: `ReportTemplate::findOrFail($templateId)` + `is_active` check → `TemplateNotFoundException`.
- Obtiene contexto del paciente: edad (desde `date_of_birth` accessor), género, últimos 10 informes (valores sin PII).
- Delega a `LlmExtractorService::extract()`.
- Persiste `LlmInteraction` (request + response payloads, processing time).

### `TranscribeReportCommand`
- Verifica permiso `report.edit` vía `PermissionService::ensure()`.
- Busca `PatientReport` via `PatientReportReadRepository::buscarPorId($id)`.
- Convierte audio WebM a MP3 vía FFmpeg (pipe-based, sin I/O a disco) si es necesario.
- Delega a `SpeechToTextService::transcribe()` para la transcripción.
- Si `diarization=true`, invoca `SpeakerClassifierService::classify()` para etiquetar segmentos como "Médico" o "Paciente".
- Persiste interacción en `LlmInteraction` (tanto éxito como fallo) con metadatos (sin PII).
- Timeout del STT: 60s (configurable).

## Services

### `LlmExtractorService`
- `extract(array $templateStructure, string $transcript, array $patientContext): array`
- Construye prompt con system message (instrucciones clínicas + campos de plantilla) y user message (transcript sanitizado + contexto del paciente).
- Sanitiza transcript: elimina fences, delimitadores, HTML tags.
- HTTP POST vía Laravel `Http` facade a API OpenAI-compatible con `response_format: json_object`.
- Timeout 30s configurable vía `config('llm.timeout')`.
- Reintento único si la respuesta no es JSON parseable.
- Validación post-LLM: descarta campos extraños no presentes en la plantilla.
- Logging seguro: solo métricas (template_id, processing_time_ms, tamaño transcript).

### `SpeechToTextService`
- `transcribe(string $audioBase64, string $mimeType, bool $diarization, ?string $language): TranscribeResult`
- Envía audio codificado en base64 al endpoint `/audio/transcriptions` del proveedor configurado (OpenAI-compatible).
- Usa `response_format: verbose_json` para obtener segmentos con timestamps.
- Timeout 60s configurable.
- Reutiliza el mismo provider/config base que LLM pero con endpoint de transcripciones.
- Conversión de WebM a MP3 vía FFmpeg antes del envío (los proveedores STT no siempre soportan WebM).

### `SpeakerClassifierService`
- `classify(array $segments): array` — clasifica segmentos de "Speaker N" a "Médico" / "Paciente".
- **Input:** `array<int, array{speaker: string, text: string, start: float, end: float}>`
- **Output:** Misma estructura pero con `speaker` reemplazado por `"Médico"` o `"Paciente"`.
- **Algoritmo:**
  1. **Heurística (rápida):** Agrupa por speaker, aplica scoring basado en patrones lingüísticos (médico → preguntas técnicas, paciente → descripción de síntomas). Si la diferencia de score entre speakers es ≥2, usa el resultado heurístico.
  2. **LLM (fallback):** Si la heurística es inconclusa, envía la conversación al LLM para clasificación semántica con `temperature=0.1` y `response_format: json_object`.
  3. **Single speaker:** Si solo hay un hablante, clasifica con la heurística directamente.
- **Patrones de paciente:** `"me duele"`, `"tengo"`, `"siento"`, `"me recetaron"`, `"estoy tomando"`, `"padezco"`, `"sufro"`.
- **Patrones de médico:** `"diagnóstico"`, `"tratamiento"`, `"prescribo"`, `"explíqueme"`, `"antecedentes"`, `"alergias"`, `"presión"`, `"frecuencia"`.
- Los segmentos se integran en el flujo post-STT, antes de devolver la respuesta al frontend.

## Exceptions

| Excepción | HTTP | Causa |
|-----------|------|-------|
| `TemplateNotFoundException` | 400 | Template no existe o inactivo |
| `ModelNotFoundException` | 404 | Report `{id}` no existe |
| `AiTimeoutException` | 500 | STT excede timeout de 60s |
| `AiResponseException` | 500 | STT devuelve respuesta inválida |
| `AiUnavailableException` | 503 | Servicio STT no disponible |

### Transcribe — Mapeo de errores completo

| Código | Cuándo ocurre | Mensaje |
|--------|--------------|---------|
| 400 | `{id}` no existe como informe | `"Informe no encontrado"` |
| 403 | Sin permiso `report.edit` | `"No tienes permisos"` |
| 404 | Audio no encontrado | `"Informe no encontrado"` |
| 413 | Archivo excede 25 MB | `"El archivo de audio excede el tamaño máximo permitido"` |
| 415 | Formato de audio no soportado | `"Formato de audio no soportado"` |
| 422 | Audio corrupto / sin contenido | `"El archivo de audio no contiene datos válidos"` |
| 500 | Error del STT (timeout, respuesta inválida) | `"Error al procesar el audio"` |
| 503 | Servicio STT no disponible | `"Servicio de transcripción temporalmente no disponible"` |

## Modelos

### `LlmInteraction` — Tabla: `llm_interactions`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `patient_report_id` | bigint unsigned | FK → `patient_reports`, cascadeOnDelete |
| `type` | string(50), nullable | DEFAULT `'extraction'`. Valores: `extraction`, `stt` |
| `request_payload` | json | Payload enviado al LLM (metadatos + prompt, sin PII) |
| `response_payload` | json, nullable | Respuesta del LLM (null si falló antes de recibir) |
| `processing_time_ms` | integer, nullable | Tiempo de procesamiento en ms |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Modelo:** `App\Models\LlmInteraction` — `$casts: ['request_payload' => 'array', 'response_payload' => 'array']`.

## Contrato del Endpoint de Transcripción

### Request (multipart/form-data)

```
POST /api/reports/{id}/transcribe
Authorization: Bearer <token>
Content-Type: multipart/form-data

audio: @dictado.mp3
language: es
diarization: true
```

### Response 200

```json
{
  "data": {
    "transcript": "Paciente de 45 años, consulta por dolor torácico...",
    "segments": [
      {
        "speaker": "Médico",
        "text": "¿Cómo se ha sentido esta semana?",
        "start": 0.0,
        "end": 3.2
      },
      {
        "speaker": "Paciente",
        "text": "He tenido mucho dolor en las rodillas.",
        "start": 3.5,
        "end": 7.1
      }
    ],
    "language": "es",
    "duration_seconds": 15.3
  },
  "meta": {},
  "message": "Transcripción completada"
}
```

### Reglas de diarización

- `speaker` usa el formato legible `"Médico"` / `"Paciente"` (clasificado por `SpeakerClassifierService`).
- Si `diarization=false`, se devuelve un solo segmento con el speaker clasificado según el contenido.
- `text` es el fragmento transcrito para ese hablante.
- `start`/`end` en segundos desde el inicio del audio.

## Contrato del Endpoint de Extracción

### Request (application/json)

```
POST /api/reports/{id}/extract-data
Authorization: Bearer <token>
Content-Type: application/json

{
  "transcript": "Texto completo de la transcripción...",
  "template_id": 42
}
```

### Response 200

```json
{
  "data": {
    "extracted_data": {
      "hcg_motivo_consulta": "Dolor torácico",
      "hcg_ta": "120/80",
      "hcg_diagnostico_principal": "Angina de pecho"
    },
    "confidence_scores": {
      "hcg_motivo_consulta": 0.95,
      "hcg_ta": 0.88,
      "hcg_diagnostico_principal": 0.76
    },
    "warnings": ["Campo 'alergias' no encontrado en la transcripción"],
    "processing_time_ms": 1250
  },
  "meta": {},
  "message": "Datos extraídos correctamente"
}
```

### Reglas del contrato `extracted_data`

- Las **keys** del objeto `extracted_data` deben coincidir con los `field.key` de la plantilla (vía `ai_help_description`).
- Valores `null`, vacíos (`""`) u omitidos son **válidos** — el frontend los deja en blanco.
- Los campos sin `ai_help_description` no deben ser objetivo del LLM.
- Tipos de campo esperados: `text`, `textarea`, `number`, `date`, `select`, `multi_select`, `radio`, `checkbox`.

## Secuencia de llamadas (transcribe → extract)

El frontend consume los endpoints en secuencia:

```
POST /reports/{id}/transcribe
  → { data: { transcript, segments, language, duration_seconds } }
       │
       └── extrae data.transcript
              │
              ▼
POST /reports/{id}/extract-data
  Body: { transcript: "...", template_id: 7 }
  → { data: { extracted_data, confidence_scores, warnings, processing_time_ms } }
```

## Protección contra Inyección de Prompts

1. **System prompt**: Construido server-side sin datos del usuario.
2. **Transcript**: Siempre en `role: "user"`.
3. **Sanitización**: Elimina fences, delimitadores, HTML tags.
4. **Structured output**: `response_format: { type: "json_object" }`.
5. **Post-validación**: Campos extraños descartados.
6. **Logging PII-safe**: Solo se persisten métricas y metadata, no el contenido del audio ni transcript.

## Formatos de Audio Aceptados

| Formato | MIME type | Prioridad |
|---------|-----------|-----------|
| MP3 | `audio/mpeg` | Alto (preferido para upload) |
| WAV | `audio/wav` | Alto |
| WebM/Opus | `audio/webm;codecs=opus` | Alto (desde navegador Chrome/Firefox/Edge) |
| MP4 | `audio/mp4` | Medio (fallback Safari) |
| OGG | `audio/ogg` | Medio |
| M4A | `audio/mp4` | Medio |
| FLAC | `audio/flac` | Bajo |

Los formatos WebM se convierten automáticamente a MP3 vía FFmpeg antes del envío al STT.

## Flujo de Datos

### Transcribe

```
POST /api/reports/{id}/transcribe
  → AuthenticateJwt
  → RequirePermissions (report.edit)
  → TranscribeReportAction
    → TranscribeReportRequest (valida audio, diarization, language)
    → TranscribeReportCommand
      → PermissionService::ensure('report.edit')
      → PatientReportReadRepository::buscarPorId($id)
      → [Si webm] ffmpeg pipe conversion → mp3
      → SpeechToTextService::transcribe(audio, mime, diarization, language)
        → Http::withToken()->timeout(60)->post('/audio/transcriptions', ...)
        → verbose_json response
      → [Si diarization] SpeakerClassifierService::classify(segments)
        → groupBySpeaker() → scoreSpeaker() heuristics
        → [Si inconcluso] → classifyByLlm() fallback
      → LlmInteraction::create({ type: 'stt', ... })
      ← TranscribeResult { transcript, segments, language, duration }
    ← 200 { data: { transcript, segments, language, duration_seconds } }
```

### Extract Data

```
POST /api/reports/{id}/extract-data
  → AuthenticateJwt
  → RequirePermissions (report.edit)
  → ExtractReportDataAction
    → ExtractReportDataRequest (valida transcript, template_id)
    → ExtractReportDataCommand
      → PermissionService::ensure('report.edit')
      → PatientReport::findOrFail($id)
      → ReportTemplate::findOrFail($templateId) + is_active check
      → Patient::find($report->patient_id) → edad, sexo
      → PatientReport::last 10 → valores
      → LlmExtractorService::extract(template, transcript, context)
        → buildSystemPrompt() + sanitizeTranscript() + buildUserMessage()
        → Http::withToken()->timeout(30)->post(...)
        → parseLlmResponse() + 1 retry
        → LlmInteraction::create()
      ← array{ extracted_data, confidence_scores, warnings, processing_time_ms }
    ← 200 { data: { ... }, meta: {}, message: "..." }
```

## Configuración

### LLM (para extract-data)

Archivo `config/llm.php` — valores desde variables de entorno:

| Env var | Default | Descripción |
|---------|---------|-------------|
| `LLM_PROVIDER` | `openai` | Proveedor LLM |
| `LLM_API_KEY` | — | API key del proveedor |
| `LLM_MODEL` | `gpt-4o-mini` | Modelo a usar |
| `LLM_BASE_URL` | `https://api.openai.com/v1` | URL base de la API |
| `LLM_TIMEOUT` | `30` | Timeout en segundos |
| `LLM_RETRY_ATTEMPTS` | `1` | Reintentos en fallo de parseo |

### STT (para transcribe)

| Env var | Default | Descripción |
|---------|---------|-------------|
| `STT_PROVIDER` | `opencode` | Proveedor STT |
| `STT_API_KEY` | — | API key del proveedor (puede reutilizar `LLM_API_KEY`) |
| `STT_MODEL` | `mimo-v2.5` | Modelo de transcripción |
| `STT_BASE_URL` | — | URL base de la API STT |
| `STT_TIMEOUT` | `60` | Timeout en segundos (audio puede tardar más que texto) |

## Estado de Desarrollo

✅ Completo — ambos endpoints implementados y funcionales. Transcribe con diarización y clasificación de hablantes. Extract-data con extracción estructurada vía LLM. Speaker classifier con heurística + fallback LLM.
