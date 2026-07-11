## Exploration: admin-clinic-logo

### Executive Summary

The clinic entity already exists with a singleton pattern (`Clinic::first()`) and an admin update endpoint (`PUT /admin/clinic`). A logo field is **already anticipated** by the system variables catalog (`{clinica.logo}` for template interpolation) but does not exist in the database or model yet. The codebase has mature file-upload patterns (from `ArchiveReportCommand` using `UploadedFile::storeAs`) and a public storage disk already configured. The main gaps are: no `logo` column in the `clinics` table, no file validation for images in the clinic update flow, no `require_permissions` middleware on the existing clinic routes, and no separate read repository for clinics.

---

### 1. What Exists

#### 1.1 Clinic Model (`app/Models/Clinic.php`)
- Fields: `nombre`, `direccion`, `telefono`, `email`, `ciudad`, `provincia`, `codigo_postal`, `web`, `cuit`
- No `logo` field — no cast, no fillable entry, no accessor
- Uses `HasFactory` trait, no `SoftDeletes`, no custom casts

#### 1.2 Clinic Migration (`2026_07_10_000002_create_clinics_table.php`)
- All fields nullable strings, no `logo` column
- `id` (bigint), `timestamps`, no soft deletes

#### 1.3 Admin Clinic Actions
| Action | File | Permission |
|--------|------|------------|
| `GetClinicAction` | `app/Http/Actions/Admin/Clinic/GetClinicAction.php` | None (only `auth.jwt` group middleware) |
| `UpdateClinicAction` | `app/Http/Actions/Admin/Clinic/UpdateClinicAction.php` | `admin.clinic.update` (checked in command) |

**Key observation**: Neither route has `require_permissions` middleware. GET is intentionally public (any authenticated user sees clinic data), PUT is guarded at the command level internally.

#### 1.4 Commands
| Command | File | What it does |
|---------|------|-------------|
| `GetClinicCommand` | `app/Commands/Admin/Clinic/GetClinicCommand.php` | Calls `ClinicSaveRepository::getOrFail()` — no permission check |
| `UpdateClinicCommand` | `app/Commands/Admin/Clinic/UpdateClinicCommand.php` | Checks `admin.clinic.update` via `PermissionService::ensure()`, then calls `repo->update()` |

#### 1.5 Repository (`app/Repositories/Clinic/ClinicSaveRepository.php`)
- **Only exists as a save repository** — no read repository. The same class handles both reads and writes.
- `getOrFail()`: returns `Clinic::firstOrFail()` (no filtering, truly singleton)
- `update(array $data)`: `Clinic::first() ?? new Clinic()` → `fill($data)` → `save()` → `fresh()`
- Repository pattern is NOT split into read/write — this is a merged repository

#### 1.6 Routes (`routes/api.php` lines 141-143)
```php
Route::get('/clinic', GetClinicAction::class);    // NO require_permissions
Route::put('/clinic', UpdateClinicAction::class);   // NO require_permissions (guarded in command)
```
Under `Route::prefix('admin')->middleware('auth.jwt')` group.

#### 1.7 Logo Already Referenced in System Variables
In `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` (line 47):
```php
new SystemVariable('clinica', 'logo', 'Logo', 'Logo o imagen institucional'),
```
This means report templates already reference `{clinica.logo}` as an interpolation variable. The frontend expects this to resolve, but it currently returns nothing.

#### 1.8 Storage & Filesystem Configuration
- **Public disk** configured: `storage/app/public` root, URL `{APP_URL}/storage`
- **Symlink**: configured in `config/filesystems.php` (`public/storage` → `storage/app/public`) but **not yet created** (no symlink exists on disk)
- `storage/app/public/.gitignore` ignores all files — uploaded logos excluded from git
- No `.gitkeep` — directory is empty except for `.gitignore`

#### 1.9 File Upload Patterns (from `ArchiveReportCommand`)
Best existing reference in `app/Commands/Reports/ArchiveReportCommand.php`:
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// In Action: validate with
$request->validate(['pdf' => 'required|file|mimetypes:application/pdf|max:10240']);

// In Command: store file  
$pdfFile->storeAs('reports', basename($filename));

// Optional: cleanup old file
if ($report->pdf_path && Storage::disk('local')->exists($report->pdf_path)) {
    Storage::disk('local')->delete($report->pdf_path);
}
```

Also, `TranscribeReportAction` uses a dedicated FormRequest (`TranscribeReportRequest`) that handles file validation via rules. `ArchiveReportAction` does inline validation (`$request->validate(...)`).

#### 1.10 Permissions & RBAC
- `admin.clinic.update` permission already seeded in migration `2026_07_10_000004_add_clinic_update_permission.php`
- No `admin.clinic.view` permission exists — GET clinic is open to all authenticated users
- Permission middleware: `require_permissions:{slug}` (default mode: `any`)
- Guard chain: `auth.jwt` → `require_permissions` (middleware) → `PermissionService::ensure()` (command)

#### 1.11 CORS
- Default allowed origin: `http://localhost:5173` (Vite dev server)
- All HTTP methods allowed (GET, POST, PUT, PATCH, DELETE, OPTIONS)
- Allowed headers: `Content-Type`, `X-Requested-With`, `Authorization`
- `supports_credentials`: true (unless wildcard origin)
- No special CORS config needed for `/storage/` — it's served by Nginx/laravel static handler, not by CORS middleware

#### 1.12 Tests
- `tests/Feature/Admin/Clinic/ClinicSettingsTest.php` — 5 tests (2 GET, 3 PUT)
- Test pattern: mock `JwtService` with anonymous classes, `RefreshDatabase`, `grantPermission()` helper
- No file upload tests exist in the clinic test file
- PHPUnit uses SQLite in-memory (no actual filesystem); file upload tests would use Laravel's `fake()` on the Storage disk
- Test database (`DB_CONNECTION=sqlite`): no migration issues for adding a `logo` column

#### 1.13 Factory & Seeder
- `ClinicFactory` — 9 fields (no logo), uses `fake()` helpers
- `ClinicSeeder` — creates a single "Materia Gris" record with empty strings

---

### 2. What's Missing

| Gap | Severity | Detail |
|-----|----------|--------|
| **No `logo` column** in `clinics` table | Critical | Migration needed to add nullable `varchar` column |
| **No file validation** in clinic update flow | High | `UpdateClinicAction` only validates text fields; needs image mime type + size validation |
| **No logo in Clinic model** | High | `fillable` array and `$casts` need a `logo` entry |
| **No separate read repository** | Medium | `ClinicSaveRepository` handles both reads and writes — hexagonal pattern recommends split |
| **No public URL generation** for stored logo | Medium | The model needs a computed `logo_url` accessor or the resource needs to build the full URL |
| **No old logo cleanup** | Medium | When a new logo is uploaded, the previous file should be deleted from storage |
| **No `require_permissions` middleware** on clinic routes | Low | GET has no permission middleware (intentional?), PUT is guarded only at command level. Adding logo upload to PUT doesn't change this. |
| **No `storage:link` symlink** | Low | `php artisan storage:link` not yet run — must be done in deployment/provisioning |
| **No `ClinicResource` class** | Low | Clinic data is returned as plain model JSON — no resource transformation needed unless we need computed fields like `logo_url` |
| **No factory `logo` field** | Low | `ClinicFactory` needs a `logo` faker (or `null` by default since it needs a real file) |

---

### 3. Architecture Decisions Needed

1. **Logo storage path**: `clinic/logo.{ext}` (single file, overwritten) or `clinic/logos/clinic_{id}_{timestamp}.{ext}` (versioned, clean old files)? The singleton pattern favors the simpler overwrite approach.

2. **Public disk vs local disk**: The `public` disk is already configured for user-accessible files. Logo MUST use `public` disk so the frontend can render it via `<img src="...">`. The existing `ArchiveReportCommand` uses `local` disk for PDFs — that's the wrong pattern for logos.

3. **Logo URL in response**: Should the GET/PUT response include a computed `logo_url` (full URL like `http://localhost/storage/clinic/logo.png`), or should the frontend build the URL from a `logo` path? The system variables catalog suggests the frontend expects a URL — a `logo_url` accessor on the model or computed in the action response is cleaner.

4. **Request content type**: Current `UpdateClinicAction` validates `$request->validate([... text fields ...])` which expects `application/json` or form-encoded data. File upload requires `multipart/form-data`. The action must be refactored to handle **both** JSON body (text-only updates) and multipart (with file). Alternatively, a **separate endpoint** `POST /admin/clinic/logo` could handle only the logo upload.

5. **Permission for logo upload**: Does uploading a logo fall under `admin.clinic.update` (same as text changes) or does it need a separate permission like `admin.clinic.upload-logo`? The existing permission `admin.clinic.update` has description "Permite modificar los datos de la clínica o institución" — logo modification IS clinic data modification.

6. **TDD approach for file uploads**: File upload tests require `Storage::fake('public')` and `UploadedFile::fake()->image('logo.png')`. The current test pattern (mocking `JwtService`) should work, but no existing integration test uses `Storage::fake()` — this would be the first.

---

### 4. Risk Areas

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| **Breaking existing PUT /admin/clinic** if switching to multipart-only | High | High | Accept both content types; or create separate logo endpoint |
| **Storage symlink not created in all environments** | Medium | Medium | Document `php artisan storage:link` as deployment step; add to Docker entrypoint |
| **Image size/dimension validation complexity** | Low | Medium | Start with basic max size (2MB) + common mime types; add dimension constraints later if needed |
| **`logo` column name vs `logo_path` / `logo_url` naming** | Low | Low | Use `logo` (matches system variable `{clinica.logo}`); it's the path, not the URL |
| **Logo not in `secreto_medico` / privacy scope** | Low | Low | Logos are institutional/public — no PHI/PII privacy concern |
| **Test pollution from fake storage** | Low | Low | `Storage::fake()` creates temp directories per test; `RefreshDatabase` trait handles this |
| **Docker/Nginx config for `/storage/` path** | Low | Medium | Verify Nginx serves static files from `public/storage`; if using Docker, the PHP-FPM container must have the symlink or a volume mount |

---

### 5. Affected Areas Summary

| Area | Files | Change Type |
|------|-------|-------------|
| **Migration** | New file `database/migrations/..._add_logo_to_clinics.php` | Add `logo` nullable string column |
| **Model** | `app/Models/Clinic.php` | Add `logo` to `$fillable`; add `logo_url` accessor |
| **Repository** | `app/Repositories/Clinic/ClinicSaveRepository.php` | Handle file storage + old file cleanup in `update()` |
| **Action** | `app/Http/Actions/Admin/Clinic/UpdateClinicAction.php` | Add file validation (mime types, max size); pass file to command |
| **Command** | `app/Commands/Admin/Clinic/UpdateClinicCommand.php` | Accept `UploadedFile`, delegate storage to repository |
| **Factory** | `database/factories/ClinicFactory.php` | Add `logo` => `null` (default for tests) |
| **Tests** | `tests/Feature/Admin/Clinic/ClinicSettingsTest.php` | Add logo upload tests with `Storage::fake` |
| **Route** | `routes/api.php` | No change needed (same `PUT /admin/clinic`) OR add `POST /admin/clinic/logo` |
| **System Variable** | `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` | No change needed (already expects logo) |
| **Documentation** | `docs/tecnica/modules/` and `docs/funcional/modulos/` | Update clinic module docs |

---

### 6. Recommendation

**Minimum viable approach** (recommended for Phase 1):
1. Add `logo` nullable string column to `clinics` via migration
2. Accept logo file in the existing `PUT /admin/clinic` via multipart form data
3. Store in public disk under `clinic/logo.{timestamp}.{ext}`
4. Delete previous logo file on upload
5. Return `logo_url` as a computed attribute in GET/PUT responses
6. Validate: `image|mimes:jpeg,png,webp|max:2048`

**Alternative approach** (cleaner separation):
1. Same as above but create a **dedicated endpoint**: `POST /admin/clinic/logo` using a new `UploadClinicLogoAction`
2. Pros: clean single responsibility, no mixed content type issues, easier to test
3. Cons: one more route, one more action/command/repository method

Given the existing architecture conventions (single-action controllers, small composable pieces), the dedicated endpoint approach aligns better with the hexagonal pattern. However, the codebase shows a preference for pragmatic simplicity (the clinic has only 2 endpoints, not a full CRUD). The decision should be made in the proposal phase.

---

### Ready for Proposal

**Yes** — all infrastructure exists. The codebase has mature file upload patterns from the report module, RBAC is fully wired, and the `{clinica.logo}` variable already shows product intent. The main questions to resolve in the proposal are: dedicated endpoint vs extending PUT, and the exact logo path/URL strategy.
