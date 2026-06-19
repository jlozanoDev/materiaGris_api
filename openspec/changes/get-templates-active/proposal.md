# Proposal: GET /api/templates/active

## Intent

Professionals with `report.create` permission need to see which report templates are active before calling `POST /reports`. Currently no public endpoint exposes this — only admin CRUD exists. This is a read-only endpoint serving the report creation flow.

## Scope

### In Scope
- `GET /api/templates/active` route (JWT-protected, `require_permissions:report.create`)
- `GetActiveTemplatesAction` (invocable, exception handling matching `ListReportsAction`)
- `GetActiveTemplatesCommand` (auth guard + `PermissionService::ensure('report.create')` + repository)
- `ReportTemplateReadRepository::listarActivas()` — new method returning active-only templates ordered by `name` ASC
- 4 feature tests (200 with data, 200 empty, 401, 403)

### Out of Scope
- Pagination (spec returns flat `data` array)
- Admin panel changes
- Template filtering by name/description (just active/inactive)
- `report.view` permission — this endpoint uses `report.create` only

## Capabilities

### New Capabilities
- `templates-active`: Retrieve all active report templates ordered by name for the report creation picker

### Modified Capabilities
None — this is a read-only query on existing data using the existing `report.create` permission.

## Approach

Hexagonal pattern: **Action → Command → Repository → Model**.

1. **Repository** — Add `listarActivas(): Collection` to `ReportTemplateReadRepository`:
   `ReportTemplate::where('is_active', true)->orderBy('name')->get()`

2. **Command** — `GetActiveTemplatesCommand` in `app/Commands/Reports/`:
   Guard: `auth()->user()` → `PermissionDeniedException('Unauthorized')` if null.
   Permission: `$this->permissionService->ensure($user, 'report.create')`.
   Returns `Collection` of `ReportTemplate` models.

3. **Action** — `GetActiveTemplatesAction` in `app/Http/Actions/Reports/`:
   `__invoke(Request): JsonResponse`. Try/catch: `PermissionDeniedException` → 403, generic `Exception` → 500 with logging. Returns `response()->json(['data' => $templates])`.

4. **Route** — New group after `reports` in `routes/api.php`:
   ```php
   Route::prefix('templates')->middleware('auth.jwt')->group(function () {
       Route::get('/active', GetActiveTemplatesAction::class)
           ->middleware('require_permissions:report.create');
   });
   ```

5. **Tests** — Feature test `GetActiveTemplatesTest` following `ReportTemplateCrudTest` patterns (mock JWT via `JwtService`, grant permission via `userPermissions` sync).

## Affected Areas

| Area | Impact | Files |
|------|--------|-------|
| Routes | +1 group, +1 route, +1 import | `routes/api.php` |
| Repository | +1 method (`listarActivas`) | `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` |
| Command | New | `app/Commands/Reports/GetActiveTemplatesCommand.php` |
| Action | New | `app/Http/Actions/Reports/GetActiveTemplatesAction.php` |
| Tests | New | `tests/Feature/Actions/Reports/GetActiveTemplatesTest.php` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Large `structure` JSON payloads in response | Low | Include full structure (frontend needs it for `ReportFillPage`); monitor response size |
| Duplicate permission check (middleware + Command) | Low | Established project pattern — double-gate is intentional |
| No active templates → empty array | Low (expected) | Return `{"data": []}` with 200 OK per product decision |

## Rollback Plan

Remove route from `routes/api.php`, delete `GetActiveTemplatesAction` and `GetActiveTemplatesCommand`, remove `listarActivas()` from repository. No migrations, no DB changes — instant rollback.

## Dependencies

- `report.create` permission already seeded and assigned to `professional` role
- `ReportTemplateReadRepository` already exists with filtering infrastructure
- No new packages, migrations, or config changes

## Success Criteria

- [ ] `GET /api/templates/active` returns 200 with active templates ordered by `name` ASC
- [ ] Inactive and soft-deleted templates are excluded
- [ ] `GET /api/templates/active` returns 200 with empty `data` when no active templates exist
- [ ] `GET /api/templates/active` returns 401 without JWT
- [ ] `GET /api/templates/active` returns 403 without `report.create` permission
- [ ] All 4 feature tests pass
