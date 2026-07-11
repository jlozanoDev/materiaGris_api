# Proposal: Admin Clinic Logo Upload

## Intent

Allow authenticated users with `admin.clinic.update` permission to upload an institutional logo via `POST /admin/clinic/logo`. The system variable `{clinica.logo}` already references this field — reports and templates expect it. The current clinic model has no logo column.

## Scope

### In Scope
- New `POST /admin/clinic/logo` endpoint (multipart, `logo` field, 5 MB max)
- Migration adding nullable `logo` column to `clinics`
- File storage on public disk: `storage/app/public/logos/{clinic_id}_{random}.{ext}`
- Public route `GET /logos/{filename}` to serve logos directly from storage, bypassing Nginx static files
- Model accessor `getLogoUrlAttribute()` returning absolute URL via route helper
- Old logo cleanup before saving new one
- Extend `GET /admin/clinic` response to include `logo_url` (null when no logo)

### Out of Scope
- Logo dimension/aspect-ratio validation
- Logo variants (thumbnails, favicon)
- Logo field on `PUT /admin/clinic` (text-only, unchanged)
- ClinicResource class (model accessor suffices)

## Capabilities

### New Capabilities
- `clinic-logo`: Upload and serve clinic institutional logo via dedicated endpoints

### Modified Capabilities
- `database-schema`: `clinics` table gains `logo` column (nullable varchar)
- `endpoints-guide`: new `POST /admin/clinic/logo` endpoint; `GET /admin/clinic` response extended
- `permissions-catalog`: `admin.clinic.update` scope now covers logo upload

## Approach

**Dedicated endpoint** — `POST /admin/clinic/logo` — confirmed over extending PUT. Single responsibility, no mixed content-type issues, cleaner validation.

**Logo serving via Laravel route** instead of Nginx static files. A public `GET /logos/{filename}` reads directly from `storage/app/public/logos/` and returns via `response()->file()`. This eliminates the `storage:link` symlink requirement and Nginx CORS configuration.

Files to create:
| File | Purpose |
|------|---------|
| `database/migrations/..._add_logo_to_clinics.php` | Add nullable `logo` varchar |
| `app/Http/Actions/Admin/Clinic/UploadClinicLogoAction.php` | Validate `logo` file (MIME, size), call command |
| `app/Http/Actions/ShowLogoAction.php` | Public, no auth — read file from public disk, return response |
| `app/Commands/Admin/Clinic/UploadClinicLogoCommand.php` | Permission check, delegate to repository |
| `tests/Feature/Admin/Clinic/ClinicLogoUploadTest.php` | TDD: auth, permission, upload, replace, invalid types |

Files to modify:
| File | Change |
|------|--------|
| `app/Models/Clinic.php` | `logo` in `$fillable`; `getLogoUrlAttribute()` accessor via `route('logo.show', $filename)` |
| `app/Repositories/Clinic/ClinicSaveRepository.php` | `updateLogo(UploadedFile)` — store + cleanup |
| `routes/api.php` | `POST /admin/clinic/logo` (auth) + `GET /logos/{filename}` (public) |

**Pattern**: `UploadedFile::storeAs()` on public disk, old file deletion before new store. URL generation uses route helper (`route('logo.show', $filename)`) instead of `Storage::url()`. Validation: `mimetypes:image/png,image/jpeg,image/svg+xml,image/webp|max:5120`.

**Permission**: verified inside Command via `PermissionService::ensure(user, 'admin.clinic.update')` — consistent with existing `UpdateClinicCommand`. The public `GET /logos` route has no auth middleware.

**TDD flow**: write tests first → red → implement migration + model + action + command + repo → green.

## Dependencies

- `admin.clinic.update` permission already seeded (migration `2026_07_10_000004`)

## Risks

| Risk | Mitigation |
|------|-----------|
| PHP serves logo files instead of Nginx | Negligible for small logo images (<5 MB); Laravel `response()->file()` streams efficiently |
| File naming collision (`clinic_id_random`) | Use `uniqid()` — negligible collision probability |
| SVG upload security (XSS in SVG) | Accept `image/svg+xml` per contract; no server-side SVG parsing needed |

## Rollback Plan

Drop `logo` column from `clinics`; remove routes, actions, command; delete uploaded files from `storage/app/public/logos/`. No data loss — logo is additive only.

## Success Criteria

- [ ] `POST /admin/clinic/logo` accepts valid PNG/JPEG/SVG/WebP ≤5 MB, returns 200 with `logo_url`
- [ ] `GET /logos/{filename}` serves the logo file with correct Content-Type and CORS headers
- [ ] `GET /admin/clinic` includes `logo_url` in response (null when no logo)
- [ ] Old logo file deleted on replacement upload
- [ ] `401` without token, `403` without permission, `415` wrong MIME, `413` oversized
- [ ] Logo URL does not require `storage:link` symlink or Nginx CORS config
- [ ] All tests pass with `Storage::fake('public')`
