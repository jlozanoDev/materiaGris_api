# Especificación Backend: POST /reports/{id}/transcribe

> **Para**: Equipo Backend (Laravel)  
> **Módulo**: Dictado y Autocompletado de Informes con IA  
> **Basado en**: OpenSpec `report-extract-data` (spec, design, proposal)  

---

## 1. Resumen

Recibe un archivo de audio con el dictado de un informe médico, lo procesa con un servicio speech-to-text (Whisper / OpenAI-compatible), y devuelve la transcripción con metadatos. El frontend usará el `transcript` como entrada para `POST /reports/{id}/extract-data`.

---

## 2. Endpoint

| Campo | Valor |
|-------|-------|
| **Método** | `POST` |
| **URL** | `/api/reports/{id}/transcribe` |
| **Content-Type** | `multipart/form-data` |
| **Auth** | Bearer token (JWT) |
| **Permiso** | `report.edit` (mismo que `extract-data` y `SaveDraftReport`) |

---

## 3. Request

### Body (multipart/form-data)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `audio` | `file` | Sí | Archivo de audio con el dictado. Formatos: `mp3`, `wav`, `webm`, `ogg`, `m4a`, `flac`. Máx. 25 MB. |
| `language` | `string` | No | Código ISO 639-1 del idioma del dictado (ej. `es`, `en`). Si se omite, el servicio lo detecta automáticamente. |

### Ejemplo

```http
POST /api/reports/42/transcribe
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
Content-Type: multipart/form-data

audio: @dictado.mp3
language: es
```

---

## 4. Response (200 OK)

```json
{
  "data": {
    "transcript": "Paciente de 45 años, consulta por dolor torácico. Refiere dolor opresivo de 3 horas de evolución...",
    "segments": [
      {
        "start": 0.0,
        "end": 4.2,
        "text": "Paciente de 45 años, consulta por dolor torácico."
      },
      {
        "start": 4.5,
        "end": 8.1,
        "text": "Refiere dolor opresivo de 3 horas de evolución..."
      }
    ],
    "language": "es",
    "duration_seconds": 15.3
  },
  "meta": {},
  "message": "Transcripción completada"
}
```

### Campos de respuesta

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `transcript` | `string` | Texto completo de la transcripción. El frontend lo pasa tal cual a `extract-data`. |
| `segments` | `array` | Segmentos con timestamps. Cada item: `start` (float, segundos), `end` (float), `text` (string). |
| `language` | `string` | Código ISO 639-1 del idioma detectado/usado. |
| `duration_seconds` | `float` | Duración total del audio en segundos. |

---

## 5. Códigos de Error

| Código | Cuándo ocurre | Response Body |
|--------|--------------|---------------|
| **400** | `audio` no enviado o `{id}` no existe | `{ "message": "Informe no encontrado" }` |
| **403** | Usuario sin permiso `report.edit` | `{ "message": "No tienes permisos" }` |
| **413** | Archivo de audio excede 25 MB | `{ "message": "El archivo de audio excede el tamaño máximo permitido" }` |
| **415** | Formato de audio no soportado | `{ "message": "Formato de audio no soportado" }` |
| **422** | Audio corrupto / sin contenido | `{ "message": "El archivo de audio no contiene datos válidos" }` |
| **500** | Error del servicio speech-to-text (timeout, respuesta inválida) | `{ "message": "Error al procesar el audio" }` |
| **503** | Servicio speech-to-text no disponible | `{ "message": "Servicio de transcripción temporalmente no disponible" }` |

---

## 6. Contrato con extract-data

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

**El backend de `transcribe` solo devuelve el texto. No necesita saber nada de plantillas ni extracción.**

---

## 7. Speech-to-Text Service

Usar el mismo provider configurado para LLM (`config/llm.php`) con endpoint `/audio/transcriptions`. La mayoría de APIs OpenAI-compatibles (OpenAI, Groq, Ollama, LM Studio) exponen este endpoint.

### Configuración (reutilizar .env existente)

```
STT_PROVIDER=openai          # mismo que LLM_PROVIDER si es compatible
STT_API_KEY=${LLM_API_KEY}   # reutilizar la misma key
STT_MODEL=whisper-1          # modelo de transcripción
STT_BASE_URL=${LLM_BASE_URL} # mismo base URL
STT_TIMEOUT=60               # segundos (audio puede tardar más que texto)
```

### Llamada HTTP (referencia)

```php
Http::withToken(config('stt.api_key'))
    ->timeout(config('stt.timeout', 60))
    ->attach('file', $audioContent, $filename)
    ->post(config('stt.base_url') . '/audio/transcriptions', [
        'model'          => config('stt.model', 'whisper-1'),
        'language'       => $language,        // opcional
        'response_format' => 'verbose_json',  // necesario para segments
    ]);
```

### Response del provider (verbose_json)

```json
{
  "text": "Paciente de 45 años...",
  "segments": [{"start": 0.0, "end": 4.2, "text": "..."}],
  "language": "es",
  "duration": 15.3
}
```

Mapeo directo a la response del endpoint: `text → transcript`, `duration → duration_seconds`. El resto pasa igual.

---

## 8. Consideraciones de Implementación

### Seguridad y PII

- **NO loggear** el contenido del audio ni del transcript. Solo loggear: `report_id`, `duration_seconds`, `language`, `audio_size_bytes`.
- Si se persiste el `transcript` en `patient_reports`, usar columna dedicada (`transcript_text` o similar).
- El audio NO se almacena en disco del servidor después de procesarlo (procesar en memoria o temp file con cleanup inmediato).

### Arquitectura (patrón hexagonal, igual que extract-data)

```
Route (routes/api.php)
  → middleware: auth.jwt
  → middleware: require_permissions:report.edit
  → TranscribeReportAction::__invoke(TranscribeReportRequest $req, int $id)
    → TranscribeReportCommand::execute($id, $audio, $language, $user)
      → PermissionService::ensure($user, 'report.edit')
      → PatientReportReadRepository::buscarPorId($id)
      → SpeechToTextService::transcribe($audio, $language)
      → Save LlmInteraction (misma tabla, type='stt')
    → Action wraps in { data, meta, message }
```

### Archivos nuevos estimados

| Archivo | Propósito |
|---------|-----------|
| `app/Http/Actions/Reports/TranscribeReportAction.php` | Action invocable |
| `app/Http/Requests/Reports/TranscribeReportRequest.php` | Valida audio (required|file|mimes|max:25600) |
| `app/Commands/Reports/TranscribeReportCommand.php` | Caso de uso |
| `app/Services/SpeechToTextService.php` | Llamada HTTP al servicio STT |
| `app/Exceptions/SttTimeoutException.php` | Timeout del servicio STT |
| `app/Exceptions/SttUnavailableException.php` | Servicio STT no disponible |
| `config/stt.php` | Configuración desde env |
| `tests/Feature/Actions/Reports/TranscribeReportTest.php` | Feature tests |

### Performance

- Timeout recomendado: **60s** (el procesamiento de audio es más lento que texto).
- La respuesta síncrona es aceptable para audios de consulta (< 5 min).
- Para audios largos (> 5 min), considerar colas asíncronas en iteración futura.

### Timeout del frontend

El frontend espera máximo 60s por la respuesta. Si el STT tarda más, el frontend mostrará error y permitirá reintentar manualmente.

---

## 9. Criterios de Aceptación

- [ ] `POST /reports/{id}/transcribe` con audio válido devuelve 200 con transcript, segments, language, duration_seconds
- [ ] Sin JWT → 401
- [ ] Sin permiso `report.edit` → 403
- [ ] Sin archivo de audio → 422
- [ ] Audio corrupto → 422
- [ ] Formato no soportado → 415
- [ ] Report `{id}` no existe → 400
- [ ] Timeout del servicio STT → 500 (la app no crashea)
- [ ] Servicio STT caído → 503
- [ ] Logs NO contienen transcript ni contenido de audio
- [ ] La respuesta usa el envelope estándar `{ data, meta, message }`
