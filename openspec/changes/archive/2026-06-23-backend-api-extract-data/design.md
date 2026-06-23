# Design: Extracción de datos clínicos con IA desde transcripción

## Technical Approach

Hexagonal architecture: `ExtractReportDataAction` → `ExtractReportDataCommand` → `LlmExtractorService` + repositories. Synchronous HTTP call to OpenAI-compatible API via Laravel `Http` facade, 30s timeout, 1 retry on parse failure. Response wrapped in standard `{ data, meta, message }` envelope.

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| LLM transport | Laravel `Http` facade, raw JSON | OpenAI PHP SDK, LangChain | Zero dependency overhead; single provider first; OpenAI-compatible API covers 90% of providers. SDKs add latency and version coupling. |
| Response envelope | Wrap in `data`: `{ data: { extracted_data, confidence_scores, warnings, processing_time_ms }, meta: {}, message: "..." }` | Flat response | Matches existing convention (see `ListReportsAction`). Frontend expects `{ data, meta, message }` envelope. |
| Config storage | Dedicated `config/llm.php` (env-backed) | `config/services.php` section | Cleaner separation; `config/services.php` is for Laravel-managed services. LLM config is a distinct domain concern. |
| Extraction key | `field` (template field's `field` property) | `field.key` | Matches actual `template_structure_snapshot` schema: `{ field, label, type, ai_help_description, required }`. |
| LlmInteraction table | New table, every call stored | Log-only, or no persistence | Required for audit, cost tracking, and debugging. `request_payload`/`response_payload` as JSON columns. |

## Data Flow

```
POST /api/reports/{id}/extract-data
    │
    ▼
ExtractReportDataAction::__invoke(ExtractReportDataRequest $req, int $id)
    │  validates transcript (required string), template_id (required int, exists)
    │
    ▼
ExtractReportDataCommand::execute($id, $transcript, $templateId, $user)
    │
    ├── PermissionService::ensure($user, 'report.edit')
    ├── PatientReportReadRepository::buscarPorId($id) → PatientReport
    │     └── template_structure_snapshot (JSON cast → array)
    ├── ReportTemplateReadRepository::buscarPorId($templateId)
    │     └── validates: exists AND is_active=true (throws TemplateNotFoundException if not)
    ├── PatientReadRepository::buscarPorId($report->patient_id) → Patient
    │     └── extract: edad, sexo, medicación (medicación: TBD — may need migration)
    ├── PatientReportReadRepository (last 10 by patient_id, ordered desc)
    │     └── extract: values only (NO PII: no names, IDs, contact)
    │
    ├── LlmExtractorService::extract(snapshot, transcript, patientContext)
    │     ├── buildSystemPrompt(flatten fields) → system message
    │     ├── sanitizeTranscript(transcript) → cleaned text
    │     ├── buildUserMessage(sanitized + patientContext) → user message
    │     ├── Http::withToken(config('llm.api_key'))
    │     │     .timeout(config('llm.timeout', 30))
    │     │     .post(config('llm.base_url') . '/chat/completions', [...])
    │     │     └── throws on timeout/5xx → LlmTimeoutException / LlmUnavailableException
    │     ├── parseLlmResponse(body, fieldKeys) → array or LlmResponseException
    │     ├── retry once on LlmResponseException
    │     └── returns [extracted_data, confidence_scores, warnings, processing_time_ms]
    │
    ├── Save LlmInteraction (report_id, request_payload, response_payload, processing_time_ms)
    │
    └── return [extracted_data, confidence_scores, warnings, processing_time_ms]
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Actions/Reports/ExtractReportDataAction.php` | Create | Invocable action. Delegates to command, maps exceptions to HTTP codes (per spec). |
| `app/Http/Requests/Reports/ExtractReportDataRequest.php` | Create | Validates `transcript` (required\|string\|min:1) and `template_id` (required\|integer\|exists:report_templates,id). |
| `app/Commands/Reports/ExtractReportDataCommand.php` | Create | Use case: permission check, report/template existence, patient context fetch, LLM delegation, interaction logging. |
| `app/Services/LlmExtractorService.php` | Create | Prompt building, transcript sanitization, HTTP call, JSON parsing with 1 retry, interaction logging. |
| `app/Exceptions/LlmTimeoutException.php` | Create | Thrown when LLM HTTP call exceeds 30s timeout. |
| `app/Exceptions/LlmResponseException.php` | Create | Thrown when LLM response is non-JSON or missing required template keys (after retry). |
| `app/Exceptions/LlmUnavailableException.php` | Create | Thrown when provider returns 503 or connection refused. |
| `app/Exceptions/TemplateNotFoundException.php` | Create | Thrown when template_id doesn't exist or is inactive for user's context. |
| `app/Models/LlmInteraction.php` | Create | Eloquent model with `$fillable`, `$casts`, `patientReport()` relation. |
| `database/migrations/{timestamp}_create_llm_interactions_table.php` | Create | `llm_interactions` table with FK to `patient_reports`. |
| `config/llm.php` | Create | LLM configuration from env vars: provider, api_key, model, base_url, timeout, retry_attempts. |
| `routes/api.php` | Modify | Add `POST /reports/{id}/extract-data → ExtractReportDataAction` with `auth.jwt` + `require_permissions:report.edit`. |
| `.env.example` | Modify | Add `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`, `LLM_BASE_URL` entries. |

## Interfaces / Contracts

### LlmExtractorService::extract signature

```php
/**
 * @param array $templateStructure  The template_structure_snapshot (sections → rows → columns → fields)
 * @param string $transcript        Raw transcription text
 * @param array $patientContext     ['edad' => int|null, 'sexo' => string|null, 'medicacion' => string|null, 'last_reports' => array]
 * @return array                    ['extracted_data' => [], 'confidence_scores' => [], 'warnings' => [], 'processing_time_ms' => int]
 * @throws LlmTimeoutException|LlmResponseException|LlmUnavailableException
 */
public function extract(array $templateStructure, string $transcript, array $patientContext): array
```

### ExtractReportDataCommand::execute signature

```php
/**
 * @throws PermissionDeniedException
 * @throws ModelNotFoundException (report)
 * @throws TemplateNotFoundException
 * @throws LlmTimeoutException|LlmResponseException|LlmUnavailableException
 */
public function execute(int $reportId, string $transcript, int $templateId, User $user): array
```

## Prompt Injection Protection

1. **System prompt**: Fully constructed server-side in `buildSystemPrompt()` — zero user data. Contains only template field definitions (key, label, type, ai_help_description) and behavioral instructions.
2. **Transcript**: Placed exclusively in `role: "user"` message, never in system prompt.
3. **Sanitization**: `sanitizeTranscript()` strips markdown fences (` ``` `, `~~~`), horizontal delimiters (`---`, `***`, `===`), HTML/XML tags (angle brackets), and normalizes whitespace.
4. **Structured output enforcement**: `response_format: { type: "json_object" }` for OpenAI-compatible; evaluated per provider.
5. **Post-validation**: `parseLlmResponse()` JSON-decodes, verifies all returned keys match flattened template field keys, rejects extra keys.

## Error Mapping

| Exception | HTTP | Message |
|-----------|------|---------|
| `ModelNotFoundException` (report) | 404 | "Informe no encontrado" |
| `PermissionDeniedException` | 403 | "No tienes permisos" |
| `ValidationException` (FormRequest) | 422 | Laravel default (422) |
| `TemplateNotFoundException` | 400 | "Plantilla no válida" |
| `LlmTimeoutException` | 500 | "Error al procesar con IA" |
| `LlmResponseException` | 500 | "Error al procesar con IA" |
| `LlmUnavailableException` | 503 | "Servicio de IA temporalmente no disponible" |

## Testing Strategy

| Layer | Test | Approach |
|-------|------|----------|
| Feature | `ExtractReportDataTest` | 10 tests: happy path (mocked LLM → 200), auth (401), permission (403), validation (422), report not found (404), template invalid (400), LLM timeout (500), malformed JSON after retry (500), LlmInteraction saved, extra fields discarded |
| Unit | `LlmExtractorServiceTest` | 7 tests: sanitize strips fences, sanitize strips delimiters, system prompt includes fields, flatten nested structure, parse valid JSON, parse invalid JSON throws, parse missing keys throws |
| Unit | `ExtractReportDataCommandTest` | Integration-style: mock LLM service, verify permission check, report lookup, template validation, patient context fetch |

## Open Questions

- [ ] **Patient `medicación` field**: Not present in current `Patient` model. Options: (a) add `medication` column via migration, (b) use empty string as default, (c) fetch from a related `Medication` model if one exists. Decision needed before `ExtractReportDataCommand` implementation.
- [ ] **`ai_help_description` in snapshot**: Current factory/default template structure doesn't include it. Implementation should handle its absence gracefully (use `label` as fallback instead of crashing). The `template_structure_snapshot` captured at report creation time may or may not include it depending on when the template was defined.
