# Tasks: Extracción de datos clínicos con IA

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~1000 new + ~3 modified |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR1: Foundation (~160L) → PR2: LlmExtractorService + unit tests (~330L) → PR3: Command + Request (~170L) → PR4: Action + Route + Feature tests (~340L) |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Foundation: config, exceptions, migration, model, env | PR 1 | ~160 lines; zero behavior, pure infrastructure |
| 2 | LlmExtractorService + 7 unit tests | PR 2 | ~330 lines; depends on PR 1 |
| 3 | Command + Request + 3 Command unit tests | PR 3 | ~170 lines; depends on PR 2 |
| 4 | Action + Route + 10 Feature tests | PR 4 | ~340 lines; depends on PR 3 |

## Phase 1: Foundation (T1–T5)

- [ ] **T1** — Create `config/llm.php` with `provider`, `api_key`, `model`, `base_url`, `timeout` (30s), `retry_attempts` (1) from env vars. No deps. ~25L.
- [ ] **T2** — Create 4 exception classes: `LlmTimeoutException`, `LlmResponseException`, `LlmUnavailableException`, `TemplateNotFoundException` (extend `Exception`). Follow `PermissionDeniedException` pattern. No deps. ~32L.
- [ ] **T3** — Create migration `create_llm_interactions_table`: `id`, `patient_report_id` (FK), `provider`, `model`, `request_payload` (json), `response_payload` (json), `processing_time_ms`, `created_at`, `updated_at`. No deps. ~35L.
- [ ] **T4** — Create `LlmInteraction` model (HasFactory, `$fillable`, `$casts` for json columns, `patientReport()` BelongsTo) + factory. Depends on T3. ~70L.
- [ ] **T5** — Add `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`, `LLM_BASE_URL` entries to `.env.example`. No deps. ~6L.

## Phase 2: Core LLM Service (T6–T9)

- [ ] **T6** — Create `LlmExtractorService`: `extract()`, `buildSystemPrompt()` (flatten template sections→rows→columns, use `field` + `ai_help_description` fallback `label`), `sanitizeTranscript()` (strip fences, delimiters, HTML), `buildUserMessage()` (transcript + patientContext: age, gender, last 10 reports), HTTP POST via `Http::withToken()->timeout(30)`, JSON parse + 1 retry, PII-safe logging, interaction persistence. Depends on T1,T2,T4. ~180L.
- [ ] **T7** — Write unit tests for `sanitizeTranscript`: strips code fences, strips delimiters, strips HTML tags, normalizes whitespace. Write FIRST, run `php artisan test --filter=LlmExtractorServiceTest`, then implement. Depends on T6. ~40L.
- [ ] **T8** — Write unit tests for prompt building: `buildSystemPrompt` includes all flattened fields with `field` key, `ai_help_description` fallback to `label`, patient age/gender in user message, last 10 reports included. Write FIRST, run, then implement. Depends on T6. ~50L.
- [ ] **T9** — Write unit tests for JSON parsing: valid JSON returns array, invalid JSON throws `LlmResponseException`, response with missing template keys throws `LlmResponseException`, extra keys discarded. Write FIRST, run, then implement. Depends on T6. ~60L.

## Phase 3: Business Logic (T10–T11)

- [ ] **T10** — Create `ExtractReportDataCommand`: `PermissionService::ensure(report.edit)`, `PatientReport::findOrFail`, `ReportTemplate::findOrFail` + active check → `TemplateNotFoundException`, fetch Patient (age, gender), fetch last 10 PatientReport values, call `LlmExtractorService::extract()`, save `LlmInteraction`, return array. Depends on T6. ~80L.
- [ ] **T11** — Write unit tests for Command: mock `LlmExtractorService`, verify permission check, report lookup, template validation, patient context fetch, interaction persisted. Write FIRST, run, then implement. Depends on T10. ~60L.

## Phase 4: HTTP Layer (T12–T14)

- [ ] **T12** — Create `ExtractReportDataRequest`: `transcript` (required|string|min:1), `template_id` (required|integer|exists:report_templates,id). Follow `SaveDraftReportRequest` pattern. Depends on T10. ~30L.
- [ ] **T13** — Create `ExtractReportDataAction`: `__invoke(Request, $id)`, delegates to Command, maps exceptions to HTTP codes (per design error mapping table: 400/403/404/422/500/503), wraps in `{ data, meta, message }` envelope. Follow `SaveDraftReportAction` pattern. Depends on T10,T12. ~50L.
- [ ] **T14** — Add route in `routes/api.php`: `Route::post('/{id}/extract-data', ExtractReportDataAction::class)->whereNumber('id')->middleware('require_permissions:report.edit')` inside existing `reports` prefix group. Add import. Depends on T13. ~3L modified.

## Phase 5: Integration Tests (T15)

- [ ] **T15** — Write 10 Feature tests using `actingWithPermission` pattern: (1) happy path 200 with mocked LLM, (2) no JWT → 401, (3) no `report.edit` → 403, (4) empty transcript → 422, (5) invalid template → 400, (6) report not found → 404, (7) LLM timeout → 500, (8) malformed JSON after retry → 500, (9) LlmInteraction saved on success, (10) extra fields discarded. Write FIRST, run `php artisan test --filter=ExtractReportDataTest`, then green. Depends on T14. ~280L.
