# Tasks: GET /api/templates/active

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~180 (8 repo + 35 cmd + 40 action + 10 route + 90 tests) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: RED — Feature Tests (TDD)

- [ ] 1.1 Create `tests/Feature/Actions/Reports/GetActiveTemplatesTest.php` — copy helpers (`mockJwtForUserId`, `grantPermission`, `actingWithPermission`, `authHeader`) from `ReportsCrudTest.php`; add 4 failing tests:
  - `test_returns_active_templates` — 3 active + 1 inactive + 1 soft-deleted templates, user with `report.create` → assert 200, count=3, `data.*` keys
  - `test_returns_empty_when_no_active` — 0 active templates, user with `report.create` → assert 200, `data` is `[]`
  - `test_returns_401_when_unauthenticated` — no JWT mock → assert 401
  - `test_returns_403_when_no_permission` — JWT user without `report.create` → assert 403

**Acceptance**: All 4 tests FAIL (`php artisan test --filter GetActiveTemplatesTest`). Run before any implementation.

## Phase 2: GREEN — Repository & Command

- [ ] 2.1 Add `listarActivas(): \Illuminate\Support\Collection` to `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` — `ReportTemplate::where('is_active', true)->orderBy('name')->get()`; SoftDeletes excludes deleted rows via global scope

- [ ] 2.2 Create `app/Commands/Reports/GetActiveTemplatesCommand.php` — inject `ReportTemplateReadRepository` + `PermissionService`; `execute()` checks `auth()->user()` not null (throws `PermissionDeniedException('Unauthorized')`), calls `$this->permissionService->ensure($user, 'report.create')`, returns `$this->repo->listarActivas()`

**Acceptance**: Command unit-level logic is testable (permission gate + repo delegation pattern matches `InitReportCommand`).

## Phase 3: GREEN — Action & Route

- [ ] 3.1 Create `app/Http/Actions/Reports/GetActiveTemplatesAction.php` — invocable; injects `GetActiveTemplatesCommand` via constructor; `__invoke(Request $request): JsonResponse` wraps `$this->command->execute()` in try/catch: `PermissionDeniedException` → 403, `\Exception` → 500 + `Log::error`, success → `response()->json(['data' => $collection], 200)`

- [ ] 3.2 Add `templates` route group to `routes/api.php` (after line 166, after the `reports` group): import `GetActiveTemplatesAction`, then `Route::prefix('templates')->middleware('auth.jwt')->group(...)` with `GET /active` → `GetActiveTemplatesAction::class` guarded by `require_permissions:report.create`

**Acceptance**: `php artisan route:list` shows `GET /api/templates/active`. All 4 Phase 1 tests PASS.

## Phase 4: REFACTOR & Verify

- [ ] 4.1 Run full test suite: `php artisan test` — confirm no regressions, 4 new tests green
- [ ] 4.2 Verify response JSON structure matches spec: `data[]` with `id`, `name`, `description`, `is_active` (bool), `structure`, `created_at`, `updated_at`; snake_case keys; no `deleted_at` or `meta`
- [ ] 4.3 Smoke-test via curl (with valid JWT token): `GET http://localhost/api/templates/active` → 200 with correct shape, or 401/403 when missing auth/permission

**Acceptance**: All tests pass. Manual curl confirms contract.
