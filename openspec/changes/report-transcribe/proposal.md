# Proposal: Transcripción de audio médico con IA (MiMo)

## Intent

Recibir audio de dictado médico, transcribirlo con diarización de hablantes vía OpenCode.ai Zen (MiMo-V2.5), y devolver transcripción con segmentos por hablante, idioma detectado y duración. Primer endpoint de file upload del proyecto.

## Scope

### In Scope
- Endpoint `POST /reports/{id}/transcribe` — multipart/form-data con archivo de audio
- Servicio `SpeechToTextService` que envía audio base64 como message content a MiMo via chat completions
- Diarización (`diarization` bool required) con formato `"Speaker N"`
- Timeout 120s configurable vía `STT_TIMEOUT`
- Formatos aceptados: `webm,mp3,wav,ogg,m4a,flac,mp4` (máx 25MB)
- Reuso de `llm_interactions` agregando columna `type` (valor `stt`)
- Configuración `config/stt.php` espejo de `config/llm.php`
- Control de acceso: JWT + permiso `report.edit`

### Out of Scope
- Colas asíncronas (Laravel Queue)
- Streaming / SSE / cancelación de petición
- Almacenamiento de audio en disco post-procesamiento
- Soporte multi-provider STT (MiMo primero, KISS)
- Frontend UI

## Capabilities

### New Capabilities
- `report-transcribe`: Transcripción de audio médico con diarización de hablantes vía IA (MiMo-V2.5)

### Modified Capabilities
- None

## Approach

**Arquitectura hexagonal estándar** (espejo de `report-extract-data`):

```
TranscribeReportAction
  → TranscribeReportRequest (validation: audio file, diarization, language)
  → TranscribeReportCommand
    → PermissionService::ensure(user, 'report.edit')
    → PatientReportReadRepository::buscarPorId(id)
    → SpeechToTextService::transcribe(audio, diarization, language)
    → Save LlmInteraction (type='stt')
```

**Decisiones técnicas clave**:
- **Provider**: OpenCode.ai Zen con MiMo-V2.5. Endpoint `https://opencode.ai/zen/go/v1/chat/completions` (OpenAI-compatible). Audio como base64 en `content` del mensaje user.
- **Prompt**: System message instruye a MiMo a transcribir con diarización. Response parseada como JSON con estructura `{ transcript, segments: [{ speaker, text, start, end }], language, duration_seconds }`.
- **Diarización**: Si `diarization=false`, un solo segmento con `"speaker": "Speaker 1"`.
- **Reintento**: 1 solo reintento si respuesta no es JSON parseable; si falla, 500.
- **Seguridad**: NO loggear transcript ni audio. Solo `report_id`, `duration_seconds`, `language`, `audio_size_bytes`.
- **Timeout**: 120s por defecto (MiMo procesa audio multimodal rápido; margen para archivos largos).

### New files

| File | Purpose |
|---|---|
| `app/Http/Actions/Reports/TranscribeReportAction.php` | Action invocable, orquesta request → command → JSON response |
| `app/Http/Requests/Reports/TranscribeReportRequest.php` | Valida audio (required file), diarization (required bool), language (optional string) |
| `app/Commands/Reports/TranscribeReportCommand.php` | Caso de uso: permisos, búsqueda de report, delegación a STT service |
| `app/Services/SpeechToTextService.php` | Prompt builder, HTTP call con audio base64, JSON parsing, retry, logging seguro |
| `app/DTOs/TranscribeReportResult.php` | DTO inmutable con transcript, segments, language, duration_seconds |
| `app/Exceptions/SttTimeoutException.php` | Timeout del servicio STT (120s) |
| `app/Exceptions/SttUnavailableException.php` | Servicio STT no disponible (503, connection refused) |
| `app/Exceptions/SttResponseException.php` | Respuesta STT inválida (JSON malformado post-retry) |
| `config/stt.php` | Configuración STT desde env: provider, api_key, model, base_url, timeout |
| `database/migrations/{timestamp}_add_type_to_llm_interactions.php` | Agrega columna `type` (varchar, default='extract') a `llm_interactions` |
| `tests/Feature/Actions/Reports/TranscribeReportTest.php` | Feature tests: happy path, auth, permisos, validación, timeout, formatos |

### Modified files

| File | Change |
|---|---|
| `routes/api.php` | Nueva ruta `POST /reports/{id}/transcribe → TranscribeReportAction` con middleware `auth.jwt` + `require_permissions:report.edit` |
| `.env.example` | Agregar `STT_PROVIDER`, `STT_API_KEY`, `STT_MODEL`, `STT_BASE_URL`, `STT_TIMEOUT` |
| `.env` | Agregar mismas variables con valores de desarrollo |

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Actions/Reports/` | New | TranscribeReportAction |
| `app/Commands/Reports/` | New | TranscribeReportCommand |
| `app/Services/` | New | SpeechToTextService |
| `app/DTOs/` | New | TranscribeReportResult |
| `app/Exceptions/` | New | SttTimeoutException, SttUnavailableException, SttResponseException |
| `routes/api.php` | Modified | Nueva ruta POST bajo grupo reports |
| `config/` | New | config/stt.php |
| `database/migrations/` | New | Migration para columna `type` en llm_interactions |
| `app/Models/LlmInteraction.php` | Modified | Agregar `type` a `$fillable` y `$casts` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| MiMo devuelve JSON inválido incluso tras reintento | Medium | 1 retry interno; si falla, 500 con mensaje genérico. Frontend permite reintento manual. |
| Timeout en audios largos (>5 min) | Medium | 120s default cubre la mayoría. Para audios más largos, colas asíncronas en iteración futura. |
| PII/PHI en logs | Low | Solo loggear métricas (report_id, duration, audio_size_bytes). Revisión de código obligatoria. |
| Primer file upload del proyecto — multipart handling inexperto en el codebase | Low | Laravel maneja multipart nativamente. FormRequest valida file/mime/size. Docker ya configurado para 100MB. |
| Diarización inexacta en MiMo | Low | Formato "Speaker N" es genérico y tolerante. El frontend solo muestra labels. |

## Rollback Plan

1. Comentar o eliminar la ruta en `routes/api.php`
2. Rollback migration de columna `type` (no destructiva: columna nueva, nullable)
3. Eliminar `config/stt.php`
4. No hay cambios de schema en tablas core — sin riesgo de datos

## Dependencies

- OpenCode.ai Zen accesible desde el servidor (API key configurable via env)
- `auth.jwt` middleware (existente)
- `require_permissions` middleware (existente)
- `report.edit` permission (existente, asignado a admin y professional)
- `PatientReportReadRepository` (existente)
- `LlmInteraction` model (existente — se agrega columna `type`)

## Success Criteria

- [ ] `POST /reports/{id}/transcribe` con audio válido + `diarization: true` devuelve 200 con transcript y segments con speaker
- [ ] `diarization: false` devuelve un solo segmento con "Speaker 1"
- [ ] Sin JWT → 401
- [ ] Sin permiso `report.edit` → 403
- [ ] Sin archivo de audio → 422
- [ ] Formato no soportado → 422
- [ ] Report `{id}` no existe → 404
- [ ] Timeout del servicio STT → 500 (la app no crashea)
- [ ] Servicio STT caído → 503
- [ ] Logs NO contienen transcript ni contenido de audio
- [ ] `LlmInteraction` guardado con `type='stt'`
- [ ] Respuesta usa envelope estándar `{ data, meta, message }`
