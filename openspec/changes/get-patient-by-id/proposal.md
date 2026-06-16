# Proposal: GET /patients/{id}

## Intent

Provide a single-patient lookup endpoint so the frontend can fetch full patient details by ID. Currently the API only supports list (`GET /patients/find`) and CRUD write operations — there is no way to retrieve one patient directly.

## Scope

### In Scope
- `GET /patients/{id}` route with `whereNumber('id')`, `auth.jwt`, and `require_permissions:patient.view`
- `GetPatientAction` (invocable, exception handling matching `GetPatientsAction`)
- `GetPatientCommand` (auth guard + `PermissionService::ensure('patient.view')` + repository call)
- `PatientReadRepository::buscarPorId(int $id): ?Patient` method
- 4 feature tests: 200 (valid), 401 (no cookie), 403 (no permission), 404 (non-existent)
- Response: direct JSON object with all 24 fields; `last_visit_at` formatted as `Y-m-d`

### Out of Scope
- Soft-delete support (model has no `SoftDeletes` trait; spec mention is incorrect)
- Input validation beyond `whereNumber` (no body, no query)
- Modifying list endpoint response format
- Bearer token auth (project uses HttpOnly cookies; spec's Bearer mention does not apply)

## Capabilities

### New Capabilities
- `patient-get-by-id`: Retrieve a single patient by primary key with all fields

### Modified Capabilities
None — this is a read-only addition to the existing patient view permission (`patient.view`).

## Approach

Follow the hexagonal pattern already established by `GetUserAction` → `GetUserCommand` → `GetUserRepository::buscarPorId()`.

1. **Route**: Add `Route::get('/{id}', GetPatientAction::class)->whereNumber('id')->middleware('require_permissions:patient.view')` inside the existing `Route::prefix('patients')->middleware('auth.jwt')` group (line 138 of `routes/api.php`).
2. **Repository**: Add `buscarPorId(int $id): ?Patient` to `PatientReadRepository` — a simple `Patient::find($id)`.
3. **Command**: `GetPatientCommand` in `app/Commands/Admin/` — check `auth()->user()` exists, call `PermissionService::ensure('patient.view')`, return `Patient|null` from repository.
4. **Action**: `GetPatientAction` in `app/Http/Actions/Patients/` — call command, return patient as JSON or 404 message. Format `last_visit_at` as date-only string. Try/catch for 403/500 matching `GetPatientsAction` pattern.
5. **Tests**: Feature test class `GetPatientTest` covering the four response codes.

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| Response as direct object (no `data` wrapper) | Matches all existing endpoints (`GetUserAction`, `GetPatientsAction`) |
| `last_visit_at` → `Y-m-d` string | Spec requires date-only; done in Action via Carbon formatting |
| Null fields return `null` | Laravel model serializes nullable DB columns as `null` by default |
| Double-gate (middleware + command) | Matches project convention; middleware is first line, command is second |
| Command lives in `Admin` namespace | Follows `GetPatientsCommand`/`GetUserCommand` precedent |
| No `{data}` pagination wrapper | This is a single-resource endpoint, not a collection |

## Affected Areas

| Area | Impact | Files |
|------|--------|-------|
| Routes | 1 new line, 1 import | `routes/api.php` |
| HTTP Action | New | `app/Http/Actions/Patients/GetPatientAction.php` |
| Command | New | `app/Commands/Admin/GetPatientCommand.php` |
| Repository | 1 new method | `app/Repositories/Patient/PatientReadRepository.php` |
| Tests | New | `tests/Feature/Patients/GetPatientTest.php` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| DB columns all nullable; spec claims `first_name`/`last_name`/`city` required | Low (read-only) | Return whatever DB holds; no validation on read |
| Spec mentions soft-deletes, model lacks trait | None (no effect) | Document discrepancy; hard-deletes will 404 naturally |
| `string` cast on model may coerce null→`""` | Low | Verify in implementation; if coercing, remove `'email'=>'string'` casts for nullable fields |

## Rollback Plan

Remove the route line from `routes/api.php`, delete the two new files (`GetPatientAction`, `GetPatientCommand`), and remove the `buscarPorId` method from `PatientReadRepository`. No DB changes, no migrations — instant rollback.

## Dependencies

- `patient.view` permission already exists in DB and is used by `GetPatientsCommand`
- No new migrations, packages, or config changes needed

## Success Criteria

- [ ] `GET /patients/{id}` returns 200 with all 24 fields for an existing patient
- [ ] `GET /patients/{id}` returns 404 for non-existent patient
- [ ] `GET /patients/{id}` returns 401 when no JWT cookie is present
- [ ] `GET /patients/{id}` returns 403 when user lacks `patient.view` permission
- [ ] All 4 feature tests pass
- [ ] Existing endpoints (`GET /patients/find`, `POST /patients`, `PUT /patients/{id}`) unaffected
