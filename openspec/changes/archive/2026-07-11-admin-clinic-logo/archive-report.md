# Archive Report: Admin Clinic Logo Upload

**Change**: admin-clinic-logo
**Date**: 2026-07-11
**Verdict**: PASS WITH WARNINGS

## What Was Built

New endpoint `POST /admin/clinic/logo` for authenticated users with `admin.clinic.update` permission to upload an institutional clinic logo. The system variable `{clinica.logo}` references this field in reports and templates.

### Features
- Multipart upload (PNG/JPEG/SVG/WebP, ≤5 MB)
- Public logo serving via `GET /logos/{filename}` — no auth, no `storage:link` needed
- Old logo auto-deleted on replacement
- `GET /admin/clinic` response extended with `logo_url` accessor (zero changes to existing GetClinicCommand/GetClinicAction)

### Architecture Decisions
- Permission check in Command via `PermissionService::ensure()` (consistent with existing pattern)
- `response()->file()` via Laravel route instead of Nginx static files
- Model accessor + `$appends` instead of ClinicResource class
- `uniqid()` for file naming to avoid microsecond collisions

## Files Changed

### Created
| File | Purpose |
|------|---------|
| `database/migrations/2026_07_11_100444_add_logo_to_clinics.php` | Add nullable `logo` varchar to `clinics` |
| `app/Http/Actions/Admin/Clinic/UploadClinicLogoAction.php` | HTTP layer: validate, delegate, map exceptions |
| `app/Http/Actions/ShowLogoAction.php` | Public logo serving from storage |
| `app/Commands/Admin/Clinic/UploadClinicLogoCommand.php` | Permission check + orchestrate upload |
| `tests/Feature/Admin/Clinic/ClinicLogoUploadTest.php` | 9 TDD feature tests |

### Modified
| File | Change |
|------|--------|
| `app/Models/Clinic.php` | `logo` in `$fillable`, `$appends = ['logo_url']`, `getLogoUrlAttribute()` accessor |
| `app/Repositories/Clinic/ClinicSaveRepository.php` | `updateLogo(UploadedFile)` — store + cleanup |
| `routes/api.php` | `POST /admin/clinic/logo` (auth) + `GET /logos/{filename}` (public, named `logo.show`) |

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `clinic-logo` | Created | 3 requirements, 8 scenarios (new domain) |
| `database-schema` | Updated | 1 requirement added (logo column documented) |
| `endpoints-guide` | Updated | 1 requirement added, 1 modified (+2 endpoints, total→40) |
| `permissions-catalog` | Updated | 1 requirement added (admin.clinic.update scope expanded) |

## Test Results

- **Total suite**: 334 passed, 0 failed
- **Logo-specific**: 9/9 passed
- **Existing clinic tests**: 5/5 passed (no regressions)

## Warnings

- **W1**: Field-name spec mismatch — spec says `logo` field, implementation uses `logo_url` (accessor). Both `logo` (filename) and `logo_url` (URL) are present in JSON response. Better design than spec anticipated.
- **W2**: No formal TDD Cycle Evidence table in apply-progress — procedural, not functional.
- **W3**: `ShowLogoAction` uses `Storage::disk('public')->response()` instead of `response()->file()` — functionally equivalent, intentional adaptation for `Storage::fake()` testability.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
