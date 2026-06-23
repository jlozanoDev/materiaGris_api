## Exploration: POST /reports/{id}/extract-data

### Current State

MateriaGris API is a Laravel 12 hexagonal architecture application. Reports are modeled with `PatientReport` (belongs to `Patient`, `User`, and `ReportTemplate`). Templates have a `structure` JSON column that defines the report fields (sections → rows → columns → `{type, label, field, required}`). At report creation, the template structure is snapshotted into `patient_reports.template_structure_snapshot`.

The endpoint receives an audio transcript (plain text) and a template ID, uses an LLM to extract clinical data matching the template fields, and returns:
- `extracted_data`: key-value map of field keys → extracted values
- `confidence_scores`: same keys → float 0.0-1.0
- `warnings`: array of informational messages
- `processing_time_ms`: integer

No existing AI/LLM integration exists — no OpenAI, Anthropic, or Ollama packages in composer.json, no .env variables for LLM config.

### Affected Areas

| File/Path | Why |
|---|---|
| `app/Models/PatientReport.php` | Already has `template_structure_snapshot` (JSON → array cast), `template_id` FK. No changes needed. |
| `app/Models/ReportTemplate.php` | Already has `structure` (JSON → array). No changes needed. |
| `routes/api.php` | New route: `POST /reports/{id}/extract-data → ExtractReportDataAction` under `prefix('reports')->middleware('auth.jwt')` |
| `app/Http/Actions/Reports/` (new) | New `ExtractReportDataAction` — single-action controller |
| `app/Http/Requests/Reports/` (new) | New `ExtractReportDataRequest` — validates `transcript` (required string, not blank) and `template_id` (required integer) |
| `app/Commands/Reports/` (new) | New `ExtractReportDataCommand` — orchestrates: permission check, template/transcript fetching, LLM call, response formatting |
| `app/Services/` (new) | New `LlmExtractorService` — handles LLM configuration, prompt building, HTTP call, JSON parsing, retry logic |
| `app/Repositories/Report/PatientReportReadRepository.php` | Used to find report by ID. Already has `buscarPorId()`. |
| `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` | Used to validate template_id. Already has `buscarPorId()`. |
| `database/migrations/` (new) | New migration: add `report.extract-data` permission to `permissions` table |
| `config/services.php` | Add `llm` configuration section (provider, api_key, model, base_url) |
| `.env` / `.env.example` | Add `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`, `LLM_BASE_URL` variables |
| `tests/Feature/Actions/Reports/` (new) | New `ExtractReportDataTest.php` — happy path, auth, permissions, validation, error handling |

### Gaps Identified (what needs to be created from scratch)

1. **LLM Service layer** — completely new. No existing HTTP client, no prompt building infrastructure, no AI response parsing.
2. **`report.extract-data` permission** — does not exist. New migration needed.
3. **Prompt building logic** — needs to flatten `template_structure_snapshot` (nested sections→rows→columns) into field list, handle `ai_help_description` (NOT present in current schema — see Risks below).
4. **JSON response parsing with retry** — spec mandates 1 retry if LLM response is not valid JSON.
5. **Config** — `.env` variables for LLM provider, API key, model, base URL.
6. **Validation** — `transcript` must not be blank (whitespace-only). `template_id` must exist and be valid for the user.

### Architecture Fit

This endpoint follows the established hexagonal pattern perfectly:

```
Route (routes/api.php)
  → middleware: auth.jwt
  → middleware: require_permissions:report.extract-data
  → ExtractReportDataAction::__invoke(ExtractReportDataRequest $request, int $id)
    → ExtractReportDataCommand::execute(int $reportId, string $transcript, int $templateId)
      → PermissionService::ensure($user, 'report.extract-data')
      → PatientReportReadRepository::buscarPorId($id) → find report
      → ReportTemplateReadRepository::buscarPorId($templateId) → validate template
      → LlmExtractorService::extract(templateStructure, transcript) → LLM call
      → returns DTO/array with extracted_data, confidence_scores, warnings, processing_time_ms
    → Action wraps in JsonResponse
```

The Action → Command → Repository pattern is well-established (see `SaveDraftReportAction` → `SaveDraftReportCommand` → `PatientReportSaveRepository`). The new flow adds a Service layer for the LLM integration, which is appropriate — Services handle cross-cutting concerns (like `JwtService`, `PermissionService`, `AuditService`).

### Dependencies

| Dependency | Source | Status |
|---|---|---|
| JWT Auth | `auth.jwt` middleware → `AuthenticateJwt` → `JwtService` | Exists, re-used |
| Permission system | `require_permissions` middleware → `PermissionService` | Exists, new slug needed |
| Report model | `PatientReport` with `template_structure_snapshot` | Exists |
| Template model | `ReportTemplate` with `structure` | Exists |
| Report read repo | `PatientReportReadRepository::buscarPorId()` | Exists |
| Template read repo | `ReportTemplateReadRepository::buscarPorId()` | Exists |
| LLM HTTP client | Laravel HTTP client (`Http` facade) or Guzzle | Built into Laravel |
| Logging | `Log` facade (PII/PHI exclusion required by spec) | Exists |

### Risks and Unknowns

1. **Template structure field name mismatch**: The spec references `field.key` but the current `template_structure_snapshot` uses `field` (not `key`). The existing structure is:
   ```json
   { "sections": [{ "rows": [{ "columns": [{ "type": "text", "label": "Diagnóstico", "field": "diagnostico", "required": true }] }] }] }
   ```
   **Decision needed**: Use `field` as the extracted key, or ask frontend/admin template builder to add `key` and `ai_help_description` fields. The `field` property IS the key — use it. Document that the extractor flattens all columns and uses their `field` value as the output key.

2. **`ai_help_description` missing**: The spec recommends including `ai_help_description` in the LLM prompt, but this field doesn't exist in the current template structure schema. **Fallback**: Use `label` as the description for the LLM. This can be enhanced later when `ai_help_description` is added to the template editor.

3. **LLM provider decision**: No provider chosen yet. The architecture should support multiple providers (OpenAI, Anthropic, Ollama) via a common interface. Suggestion: use a strategy pattern — `LlmExtractorService` as the facade, with provider-specific adapters injected based on config.

4. **No OpenAI/Anthropic SDK in composer.json**: Need to add a package. Options:
   - `openai-php/client` for OpenAI
   - `anthropic-php/anthropic` for Anthropic
   - Or use Laravel's `Http` facade for raw API calls (simpler, fewer dependencies)
   - **Recommendation**: Start with raw `Http` facade calls to OpenAI-compatible API. Most providers (OpenAI, Ollama, local LLMs) are OpenAI-compatible. This avoids vendor lock-in and extra dependencies.

5. **Timeout handling**: Spec says 30s timeout. The LLM call must honor this. Laravel Http facade supports `timeout()`.

6. **PII/PHI logging prohibition**: Spec explicitly forbids logging transcript or extracted data. The service MUST NOT log these. Only log `template_id`, `processing_time_ms`, and transcript character count.

7. **Permission scope**: Should `report.extract-data` be an independent permission, or piggyback on `report.edit`? The spec says "Usuario sin permiso para editar el informe → 403". **Decision**: Use `report.edit` for now (same as SaveDraftReportAction), since extraction is part of editing. Optional: add dedicated `report.extract-data` later.

8. **Field type handling**: The template has `type: "text"` but the spec says extracted values can be string, number, boolean, null, or array. The LLM could return values that don't match the field type. **Decision**: The backend should NOT validate types — pass through whatever the LLM returns. The frontend handles display.

### Estimated Effort

- **Complexity**: Medium-High
- **New files to create**: ~6-8
  - `ExtractReportDataAction`
  - `ExtractReportDataRequest`
  - `ExtractReportDataCommand`
  - `LlmExtractorService` (or `app/Services/LlmExtractorService.php`)
  - `app/Services/Llm/OpenAiAdapter.php` (or similar)
  - Migration for `report.extract-data` permission
  - Feature test: `ExtractReportDataTest`
  - (Optional) Unit test for `LlmExtractorService`
- **Files to modify**: ~4
  - `routes/api.php`
  - `config/services.php`
  - `.env.example`
  - `.env`
- **Estimated time**: 4-6 hours (including tests)
- **Hardest part**: Prompt engineering and LLM response parsing reliability

### Key Files Found

| File | Description |
|---|---|
| `app/Models/PatientReport.php` | Report model with `template_structure_snapshot` (JSON→array), `values`, belongs to Patient, User, Template |
| `app/Models/ReportTemplate.php` | Template model with `structure` (JSON→array), `is_active`, SoftDeletes |
| `routes/api.php` | API routes — reports group at line 147, pattern: `Route::post('/{id}/sign', SignReportAction::class)` |
| `app/Http/Actions/Reports/SaveDraftReportAction.php` | Pattern reference: Action receives FormRequest, delegates to Command, catches exceptions → JSON |
| `app/Http/Actions/Reports/InitReportAction.php` | Pattern reference: simple Action → Command flow |
| `app/Commands/Reports/SaveDraftReportCommand.php` | Pattern reference: permission checking, ownership validation, repository call |
| `app/Repositories/Report/PatientReportSaveRepository.php` | Write repo — `iniciar()` copies `$template->structure` into snapshot |
| `app/Repositories/Report/PatientReportReadRepository.php` | Read repo — `buscarPorId()` with eager-loaded relations |
| `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` | Template read repo — `buscarPorId()` |
| `app/Services/PermissionService.php` | Permission system — `ensure($user, $slug)` |
| `app/Http/Middleware/AuthenticateJwt.php` | JWT auth — sets `auth()->user()` from Bearer token |
| `app/Http/Middleware/RequirePermissions.php` | Permission gate — checks user permissions before reaching Action |
| `database/migrations/2026_06_09_000002_create_patient_reports_table.php` | Patient reports schema |
| `database/migrations/2026_06_09_000001_create_report_templates_table.php` | Report templates schema |
| `database/migrations/2026_06_09_000003_add_report_permissions.php` | Existing report permissions (view, create, edit, sign, close, download-pdf) |
| `database/factories/PatientReportFactory.php` | Test factory with snapshot structure format |
| `tests/Feature/Actions/Reports/ReportsCrudTest.php` | Pattern reference: auth mocking, permission granting, test structure |
| `app/Enums/ReportStatus.php` | Draft, Signed, Closed |
| `composer.json` | No AI/LLM packages currently |
| `.env` | No LLM configuration currently |
| `config/services.php` | No LLM service config currently |

### Recommendation

Proceed with implementation. The architecture is solid and the patterns are clear. The main decision to make before coding is: **LLM provider strategy and which field name to use from the template structure** (`field` vs `key`). I recommend:

1. Use `field` as the extraction key (matches existing structure)
2. Use `label` as the field description for the LLM prompt
3. Use Laravel `Http` facade for OpenAI-compatible API calls (max flexibility)
4. Use `report.edit` permission for access control (no new permission needed initially)
5. Add `LLM_*` environment variables

### Ready for Proposal
**Yes** — architecture is clear, patterns are well-established, and all dependencies exist. The only unresolved questions are the LLM provider choice and the `ai_help_description` field gap, both of which can be addressed with pragmatic defaults.
