# Archive Report: backend-api-extract-data

**Archived**: 2026-06-23
**Source branch**: stacked PRs → main (Foundation, Service, Command, HTTP/Tests)

## Change Summary

New endpoint `POST /reports/{id}/extract-data` — receives audio transcript text + template ID, uses OpenAI-compatible LLM to extract clinical data matching template fields, returns structured JSON with confidence scores and warnings.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| report-extract-data | Created | Full spec copied to `openspec/specs/report-extract-data/spec.md` (10 requirements, 19 scenarios) |

## Archive Contents

- `proposal.md` ✅ — Intent, scope, approach, risks
- `specs/report-extract-data/spec.md` ✅ — 10 requirements × 19 scenarios
- `design.md` ✅ — Architecture decisions, data flow, error mapping
- `tasks.md` ✅ — 15/15 tasks complete

## Deliverables

### Files Created (19)
- `config/llm.php`
- `app/Exceptions/LlmTimeoutException.php`
- `app/Exceptions/LlmResponseException.php`
- `app/Exceptions/LlmUnavailableException.php`
- `app/Exceptions/TemplateNotFoundException.php`
- `database/migrations/2026_06_22_165208_create_llm_interactions_table.php`
- `app/Models/LlmInteraction.php`
- `database/factories/LlmInteractionFactory.php`
- `app/Services/LlmExtractorService.php`
- `app/Commands/Reports/ExtractReportDataCommand.php`
- `app/Http/Requests/Reports/ExtractReportDataRequest.php`
- `app/Http/Actions/Reports/ExtractReportDataAction.php`
- `tests/Unit/Services/LlmExtractorServiceTest.php`
- `tests/Unit/Commands/ExtractReportDataCommandTest.php`
- `tests/Feature/Actions/Reports/ExtractReportDataTest.php`
- `tests/Feature/Migrations/LlmInteractionMigrationTest.php`
- `tests/Feature/Models/LlmInteractionModelTest.php`
- `tests/Unit/Exceptions/LlmExceptionsTest.php`
- `tests/Unit/LlmConfigTest.php`

### Files Modified (4)
- `routes/api.php` — added route
- `.env.example` — added LLM_* vars
- `database/factories/ReportTemplateFactory.php` — ai_help_description on fields
- `app/Services/LlmExtractorService.php` — removed dead logInteraction() code

## Verification

- **200 tests, 673 assertions — ALL PASS**
- 1 CRITICAL (timeout handling) — resolved
- 3 non-blocking warnings remaining

## Key Decisions

- Extraction key: `field` (matches template `template_structure_snapshot` schema)
- Permission: reuse `report.edit` (same gate as SaveDraftReport)
- Patient context: age + gender from Patient model, medication from last 10 reports
- LLM transport: Laravel Http facade, OpenAI-compatible (zero dependencies)
- Prompt injection: system/user message separation, transcript sanitization, structured output
- All LLM calls persisted to `llm_interactions` (success + failure)
- PII/PHI: only metadata logged, never transcript content

## Documentation Updated

- `docs/tecnica/guia-endpoints-api.md` — added endpoint entry
- `docs/tecnica/modules/reports/modulo-dictado-autocompletado.md` — created
- `docs/funcional/modulos/dictado-autocompletado.md` — created
- `docs/funcional/flujos/dictado-autocompletado.md` — created
- `docs/INDICE.md` — updated
- `docs/tecnica/INDICE.md` — updated
- `docs/funcional/INDICE.md` — updated

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
