# Módulo de Dictado y Autocompletado de Informes — Documentación Técnica

> **Módulo**: Reports — Extracción de datos clínicos con IA
> **Especificación detallada**: [`docs/tecnica/backend-api-extract-data.md`](../../backend-api-extract-data.md)

## Rutas

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| POST | `/api/reports/{id}/extract-data` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `ExtractReportDataAction` |

## Actions

### `ExtractReportDataAction`
- Action invocable que orquesta `ExtractReportDataRequest` → `ExtractReportDataCommand`.
- Recibe `transcript` (string) y `template_id` (int) del request body.
- Mapea excepciones a códigos HTTP según tabla de error mapping.
- Responde con `{ data: { extracted_data, confidence_scores, warnings, processing_time_ms }, meta: {}, message }`.

## Requests

### `ExtractReportDataRequest`
- `transcript`: required, string, min:1 — texto de la transcripción.
- `template_id`: required, integer, exists:report_templates,id — ID de plantilla activa.

## Commands (Use Cases)

### `ExtractReportDataCommand`
- Verifica permiso `report.edit` vía `PermissionService::ensure()`.
- Busca `PatientReport::findOrFail($reportId)`.
- Valida template: `ReportTemplate::findOrFail($templateId)` + `is_active` check → `TemplateNotFoundException`.
- Obtiene contexto del paciente: edad (desde `date_of_birth` accessor), género, últimos 10 informes (valores sin PII).
- Delega a `LlmExtractorService::extract()`.
- Persiste `LlmInteraction` (request + response payloads, processing time).

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

## Exceptions

| Excepción | HTTP | Causa |
|-----------|------|-------|
| `TemplateNotFoundException` | 400 | Template no existe o inactivo |
| `LlmTimeoutException` | 500 | LLM excede timeout de 30s |
| `LlmResponseException` | 500 | LLM devuelve JSON inválido (tras reintento) |
| `LlmUnavailableException` | 503 | LLM no disponible (503 o conexión rechazada) |

## Modelos

### `LlmInteraction` — Tabla: `llm_interactions`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `patient_report_id` | bigint unsigned | FK → `patient_reports`, cascadeOnDelete |
| `request_payload` | json | Prompt + configuración enviada al LLM |
| `response_payload` | json, nullable | Respuesta del LLM (null si falló antes de recibir) |
| `processing_time_ms` | integer, nullable | Tiempo de procesamiento en ms |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

## Protección contra Inyección de Prompts

1. **System prompt**: Construido server-side sin datos del usuario.
2. **Transcript**: Siempre en `role: "user"`.
3. **Sanitización**: Elimina fences, delimitadores, HTML tags.
4. **Structured output**: `response_format: { type: "json_object" }`.
5. **Post-validación**: Campos extraños descartados.

## Flujo de Datos

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

Archivo `config/llm.php` — valores desde variables de entorno:

| Env var | Default | Descripción |
|---------|---------|-------------|
| `LLM_PROVIDER` | `openai` | Proveedor LLM |
| `LLM_API_KEY` | — | API key del proveedor |
| `LLM_MODEL` | `gpt-4o-mini` | Modelo a usar |
| `LLM_BASE_URL` | `https://api.openai.com/v1` | URL base de la API |
| `LLM_TIMEOUT` | `30` | Timeout en segundos |
| `LLM_RETRY_ATTEMPTS` | `1` | Reintentos en fallo de parseo |

## Estado de Desarrollo

✅ Completo — endpoint implementado, testeado (200 tests, 673 assertions), 4 PRs fusionados a main.
