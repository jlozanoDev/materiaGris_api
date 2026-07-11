# Tasks: Admin Clinic Logo Upload

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~385 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Foundation (migration + model + repo) | PR 1 (main) | Independent infrastructure |
| 2 | Command + Actions + Routes | PR 1 (main) | Depends on unit 1 |
| 3 | Feature Test (TDD — RED before GREEN) | PR 1 (main) | Written first, verified last |
| 4 | Documentation | PR 1 (main) | Endpoint guide, DB doc, permissions catalog |

## Phase 1: TDD — Write Failing Feature Test (RED)

- [x] 1.1 Create `tests/Feature/Admin/Clinic/ClinicLogoUploadTest.php` with 9 test cases using `RefreshDatabase`, `Storage::fake('public')`, and existing helpers (`mockJwtForUserId`, `grantPermission`, `authHeader`). All tests should fail initially (migration + routes do not exist yet).

## Phase 2: Foundation (GREEN — infrastructure)

- [x] 2.1 Create migration `database/migrations/2026_07_11_100444_add_logo_to_clinics.php`: add nullable `varchar(255) logo` column to `clinics` after `cuit`
- [x] 2.2 Modify `app/Models/Clinic.php`: add `logo` to `$fillable`, add `protected $appends = ['logo_url']`, add `getLogoUrlAttribute(): ?string` using `route('logo.show', ['filename' => $this->logo])`
- [x] 2.3 Modify `app/Repositories/Clinic/ClinicSaveRepository.php`: add `updateLogo(UploadedFile $file): Clinic` — `firstOrFail()`, delete old logo if exists via `Storage::disk('public')->delete()`, store new file as `logos/{clinic_id}_{uniqid()}.{ext}` via `storeAs('logos', ...)`, update DB row, return `fresh()`

## Phase 3: Business Logic (GREEN — command)

- [x] 3.1 Create `app/Commands/Admin/Clinic/UploadClinicLogoCommand.php`: constructor injects `ClinicSaveRepository` + `PermissionService`; `execute(UploadedFile $file): Clinic` calls `$this->permissionService->ensure($user, 'admin.clinic.update')` then `$this->repo->updateLogo($file)`

## Phase 4: HTTP Layer (GREEN — actions + routes)

- [x] 4.1 Create `app/Http/Actions/Admin/Clinic/UploadClinicLogoAction.php`: `__invoke(Request $request)` validates `logo` field (`required|file|mimetypes:image/png,image/jpeg,image/svg+xml,image/webp|max:5120`), delegates to `UploadClinicLogoCommand`, maps exceptions to HTTP status (422/403/404/500), returns `response()->json($clinic)`
- [x] 4.2 Create `app/Http/Actions/ShowLogoAction.php`: `__invoke(string $filename)` uses `Storage::disk('public')->response()` for file serving with correct MIME detection
- [x] 4.3 Modify `routes/api.php`: add import statements + `POST /admin/clinic/logo` inside admin group (no `require_permissions` middleware — permission checked in Command), add `GET /logos/{filename}` as public route named `logo.show` outside any middleware group

## Phase 5: Verify + Document (REFACTOR)

- [x] 5.1 Run `php artisan test --filter ClinicLogoUploadTest` — all 9 tests must pass
- [x] 5.2 Update `docs/tecnica/estructura-base-datos.md`: add `logo` nullable varchar to clinics table entry
- [x] 5.3 Update `docs/tecnica/guia-endpoints-api.md`: add `POST /admin/clinic/logo` and `GET /logos/{filename}` with full docs; note `logo_url` in `GET /admin/clinic` response; update summary table count (+2)
- [x] 5.4 Update `docs/tecnica/modelo-permisos-roles.md` and `docs/funcional/modulos/administracion/permisos.md`: note `admin.clinic.update` covers both `PUT /admin/clinic` and `POST /admin/clinic/logo`
- [x] 5.5 Run full test suite: `php artisan test` — no regressions
