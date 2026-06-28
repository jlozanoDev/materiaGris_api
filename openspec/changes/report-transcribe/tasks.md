# Tasks: Transcripción de audio médico con IA (MiMo)

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~1050 (new + modified) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR1: Foundation (~77L) → PR2: STT Service + DTO + 7 unit tests (~370L) → PR3: Command + Request + 5 unit tests (~240L) → PR4: Action + Route + 12 feature tests (~364L) |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Foundation: config/stt.php, 3 STT exceptions, migration, model update, env vars | PR 1 | ~77 lines; zero behavior, pure infrastructure |
| 2 | SpeechToTextService + TranscribeReportResult DTO + 7 unit tests | PR 2 | ~370 lines; depends on PR 1 |
| 3 | TranscribeReportCommand + TranscribeReportRequest + 5 command unit tests | PR 3 | ~240 lines; depends on PR 2 |
| 4 | TranscribeReportAction + Route + 12 Feature tests | PR 4 | ~364 lines; depends on PR 3 |

## Phase 1: Foundation (T1–T5)

- [x] **T1** — Create `config/stt.php` with `provider`, `api_key`, `model`, `base_url`, `timeout` (default 120s) from env vars. Mirror `config/llm.php` structure exactly. ~25L.
- [x] **T2** — Create 3 STT exception classes: `SttTimeoutException`, `SttResponseException`, `SttUnavailableException` (all extend `Exception`, with `$message` default + `getHttpCode(): int` method). Follow `LlmTimeoutException` pattern. ~24L combined.
- [x] **T3** — Create migration `{timestamp}_add_type_to_llm_interactions.php`: adds `type` column (`varchar`, default `extraction`, nullable) to `llm_interactions` table. ~20L.
- [x] **T4** — Update `app/Models/LlmInteraction.php`: add `'type'` to `$fillable` and `$casts` (as `string`). Add constant `TYPE_STT = 'stt'` for reference. ~4L.
- [x] **T5** — Add `STT_PROVIDER`, `STT_API_KEY`, `STT_MODEL`, `STT_BASE_URL`, `STT_TIMEOUT` to `.env.example`. Follow LLM_ section format. ~6L.

## Phase 2: Core STT Service (T6–T9)

- [ ] **T6 (RED)** — Write 7 unit tests in `tests/Unit/Services/SpeechToTextServiceTest.php`: (1) base64 encoding preserves MIME-type mapping, (2) system prompt includes diarization instruction when true, (3) system prompt includes language hint when set, (4) user message content parts structure, (5) parse valid transcription JSON returns expected array, (6) parse invalid JSON throws `SttResponseException`, (7) parse missing keys throws `SttResponseException`. Write FIRST, run `php artisan test --filter=SpeechToTextServiceTest` → RED. ~90L.
- [ ] **T7 (GREEN)** — Create `app/Services/SpeechToTextService.php`: constructor receives `array $config` via DI, `transcribe(array $options)` method. Implements: `encodeAudioBase64()`, `buildSystemPrompt()`, `buildUserMessage()`, `buildRequestPayload()`, `parseTranscriptionResponse()` with 1 retry, PII-safe logging (only `report_id`, `duration_seconds`, `language`, `audio_size_bytes`), `extractAssistantContent()`, `mapMimeToFormat()`. Uses `Http::withToken()->timeout()->post()`. Throws `SttTimeoutException`, `SttResponseException`, `SttUnavailableException`. Follow `LlmExtractorService` constructor pattern. ~250L.
- [ ] **T8 (GREEN)** — Create `app/DTOs/TranscribeReportResult.php`: immutable DTO with `transcript` (string), `segments` (array), `language` (string), `duration_seconds` (float). Constructor validates types. ~30L.
- [ ] **T9 (GREEN)** — Run `php artisan test --filter=SpeechToTextServiceTest` → GREEN. All 7 tests pass.

## Phase 3: Business Logic (T10–T12)

- [ ] **T10 (RED)** — Write 5 unit tests in `tests/Unit/Commands/Reports/TranscribeReportCommandTest.php`: (1) verify `PermissionService::ensure(user, 'report.edit')` is called, (2) verify `PatientReportReadRepository::buscarPorId` is called, (3) verify `SpeechToTextService::transcribe` is called with correct options, (4) verify `LlmInteraction` created with `type='stt'` on success, (5) verify `LlmInteraction` created with `type='stt'` and error info on failure. Use mocked STT service, follow `ExtractReportDataCommandTest` pattern. Write FIRST, run `php artisan test --filter=TranscribeReportCommandTest` → RED. ~80L.
- [ ] **T11 (GREEN)** — Create `app/Commands/Reports/TranscribeReportCommand.php`: `execute(int $reportId, UploadedFile $audio, bool $diarization, ?string $language, User $user): array`. Permission check → report lookup via `PatientReportReadRepository::buscarPorId` → build PII-safe `request_payload` metadata → `SpeechToTextService::transcribe()` → save `LlmInteraction` (type=stt) on BOTH success and failure → return array. Follow `ExtractReportDataCommand` structure. ~120L.
- [ ] **T12 (GREEN)** — Create `app/Http/Requests/Reports/TranscribeReportRequest.php`: validates `audio` (required, file, mimes:webm,mp3,wav,ogg,m4a,flac,mp4, max:25000), `diarization` (required, boolean), `language` (optional, string, size:2). Override `failedValidation()` to return 415 for `audio.mimes` failures and 413 for `audio.max` failures. Override `messages()` for Spanish error messages. Follow `ExtractReportDataRequest` pattern. ~40L.
- [ ] Run `php artisan test --filter=TranscribeReportCommandTest` → GREEN. All 5 tests pass.

## Phase 4: HTTP Wiring (T13–T15)

- [ ] **T13 (GREEN)** — Create `app/Http/Actions/Reports/TranscribeReportAction.php`: `__invoke(TranscribeReportRequest $req, int $id): JsonResponse`. Delegates to `TranscribeReportCommand::execute($id, $req->file('audio'), $req->validated('diarization'), $req->validated('language'), auth()->user())`. Maps exceptions to HTTP codes per design error mapping (404/403/415/413/500/503). Wraps response in `{ data, meta, message }` envelope with `message: "Transcripción completada"`. Follow `ExtractReportDataAction` pattern. ~60L.
- [ ] **T14 (GREEN)** — Add route in `routes/api.php` inside reports prefix group: `Route::post('/{id}/transcribe', TranscribeReportAction::class)->whereNumber('id')->middleware('require_permissions:report.edit');`. Add `use App\Http\Actions\Reports\TranscribeReportAction;` import (alphabetical order). ~4L.
- [ ] **T15 (RED→GREEN)** — Write 12 Feature tests in `tests/Feature/Actions/Reports/TranscribeReportTest.php`: (1) happy path with diarization → 200, envelope, segments with Speaker N, (2) happy path without diarization → single Speaker 1, (3) no JWT → 401, (4) no `report.edit` → 403, (5) audio missing → 422, (6) unsupported MIME → 415, (7) file too large → 413, (8) report not found → 404, (9) STT timeout → 500, (10) malformed JSON after retry → 500, (11) STT unavailable → 503, (12) LlmInteraction saved with `type='stt'`. Use `mockJwtForUserId`, `actingWithPermission`, `authHeader` helpers. Mock STT service via `$this->partialMock()`. Write FIRST, run `php artisan test --filter=TranscribeReportTest` → RED, then implement mocks → GREEN. ~350L.

## Phase 5: Verification

- [ ] Run full test suite: `php artisan test` — all existing + new tests pass. Verify no regressions.
- [ ] Run `php artisan route:list` — confirm `POST reports/{id}/transcribe` appears with correct middleware.
- [ ] Manual validation: check `.env` STT_* variables are set and `config('stt')` returns expected values.
