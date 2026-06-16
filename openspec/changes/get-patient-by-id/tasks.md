# Tasks: GET /patients/{id}

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 200-250 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | size-exception — budget not at risk |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Route + action + command + repo + all 4 tests | Single PR | Under 400 lines; TDD (RED→GREEN→REFACTOR) |

## Phase 1: RED — Failing Tests

- [x] 1.1 Create `tests/Feature/Patients/GetPatientTest.php` with 4 scenarios: 200 (valid patient returns all 24 fields + `age`/`full_name`), 401 (no auth header → `"Unauthorized"`), 403 (authenticated but lacks `patient.view`), 404 (non-existent ID → `"Paciente no encontrado"`). Follow `PatientsCrudTest` pattern: `RefreshDatabase`, `fakeToken()`, `Permission::firstOrCreate`, `syncWithoutDetaching`, `$this->app->instance(JwtService::class, $mock)`. Run `php artisan test --filter=GetPatientTest` — expect ALL RED.

## Phase 2: GREEN — Implementation

- [x] 2.1 Add `buscarPorId(int $id): ?Patient` to `app/Repositories/Patient/PatientReadRepository.php` — one-liner `return Patient::find($id);`.
- [x] 2.2 Create `app/Commands/Admin/GetPatientCommand.php` — constructor injection `PatientReadRepository $leer` + `PermissionService $permissionService`. `execute(int $id): ?Patient`: checks `auth()->user()`, calls `$permissionService->ensure('patient.view')`, returns `$this->leer->buscarPorId($id)`. Follow `GetPatientsCommand` pattern.
- [x] 2.3 Create `app/Http/Actions/Patients/GetPatientAction.php` — constructor injection `GetPatientCommand`. `__invoke(Request $request, $id): JsonResponse` wraps `execute()` in try/catch matching `GetPatientsAction` (PermissionDeniedException→403, \Exception→500). `execute(int $id)` calls command, null→404 `"Paciente no encontrado"`, formats `last_visit_at` via Carbon→`Y-m-d`, returns `response()->json($patient)`.
- [x] 2.4 Add import `use App\Http\Actions\Patients\GetPatientAction;` at line 20 and route `Route::get('/{id}', GetPatientAction::class)->whereNumber('id')->middleware('require_permissions:patient.view');` at line 141 in `routes/api.php`.

## Phase 3: REFACTOR — Verify & Polish

- [x] 3.1 Run `php artisan test --filter=GetPatientTest` — confirm ALL GREEN (200 with full fields, 401, 403, 404).
- [x] 3.2 Verify null fields serialize as JSON `null` (not `""`). Confirm `last_visit_at:null`, `gender:null`, `email:null` in 200 response for a patient with null values.
- [x] 3.3 Verify `last_visit_at` formats as `Y-m-d` (no time component) and `date_of_birth` as `Y-m-d`.
- [x] 3.4 Run full suite `php artisan test` — confirm no regressions in `PatientsCrudTest`, `AdminPatientsRouteTest`, or other endpoints.
