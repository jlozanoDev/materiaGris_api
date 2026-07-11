# Design: Admin Clinic Logo Upload

## 1. Component Map

| File | Action | Responsibility |
|------|--------|----------------|
| `app/Http/Actions/Admin/Clinic/UploadClinicLogoAction.php` | **Create** | HTTP layer: validate file, call command, map exceptions to HTTP status |
| `app/Http/Actions/ShowLogoAction.php` | **Create** | Public: read file from `storage/app/public/logos/`, return `response()->file()` |
| `app/Commands/Admin/Clinic/UploadClinicLogoCommand.php` | **Create** | Business logic: permission check, orchestrate old-file deletion + new-file store |
| `app/Models/Clinic.php` | **Modify** | Add `logo` to `$fillable` + `$appends`; add `getLogoUrlAttribute()` |
| `app/Repositories/Clinic/ClinicSaveRepository.php` | **Modify** | Add `updateLogo(UploadedFile)` — store file + update DB row |
| `database/migrations/XXXX_add_logo_to_clinics.php` | **Create** | Add nullable `varchar(255) logo` column to `clinics` |
| `routes/api.php` | **Modify** | Add `POST /admin/clinic/logo` (auth) + `GET /logos/{filename}` (public, named `logo.show`) |
| `tests/Feature/Admin/Clinic/ClinicLogoUploadTest.php` | **Create** | TDD: auth, permission, upload, replace, invalid MIME, oversized |

**Nothing is deleted.** `GetClinicCommand` and `GetClinicAction` require **zero changes** — the `logo_url` accessor is included automatically via `$appends`.

## 2. Data Flow

```
POST /admin/clinic/logo  (multipart/form-data, field: logo)
  │
  ▼
UploadClinicLogoAction::__invoke(Request)
  │  $request->validate([ 'logo' => 'required|file|mimetypes:image/png,image/jpeg,image/svg+xml,image/webp|max:5120' ])
  │
  ▼
UploadClinicLogoCommand::execute(user, UploadedFile $file)
  │  PermissionService::ensure(user, 'admin.clinic.update')
  │
  ▼
ClinicSaveRepository::updateLogo(UploadedFile $file): Clinic
  │  1. $clinic = Clinic::firstOrFail()
  │  2. if ($clinic->logo) → Storage::disk('public')->delete('logos/' . $clinic->logo)
  │  3. $filename = $clinic->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
  │  4. $file->storeAs('logos', $filename, 'public')
  │  5. $clinic->update(['logo' => $filename])
  │  6. return $clinic->fresh()  ← accessor computes logo_url via route('logo.show', filename)
  │
  ▼
UploadClinicLogoAction → response()->json($clinic)
  → { "logo_url": "http://localhost/logos/1_abc123.png", ... }

GET /logos/{filename}  (public, no auth)
  │
  ▼
ShowLogoAction::__invoke($filename)
  │  $path = storage_path('app/public/logos/' . $filename)
  │  return response()->file($path)   ← correct Content-Type from MIME detection
```

## 3. Class Signatures

### UploadClinicLogoAction
```php
class UploadClinicLogoAction
{
    public function __construct(private UploadClinicLogoCommand $command) {}
    public function __invoke(Request $request): JsonResponse
}
```

### ShowLogoAction
```php
class ShowLogoAction
{
    public function __invoke(string $filename): mixed  // response()->file() return type
}
```

### UploadClinicLogoCommand
```php
class UploadClinicLogoCommand
{
    public function __construct(
        private ClinicSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}
    public function execute(User $user, UploadedFile $file): Clinic
}
```

### ClinicSaveRepository::updateLogo
```php
public function updateLogo(UploadedFile $file): Clinic
```

## 4. Database Migration

```php
// database/migrations/XXXX_XX_XX_XXXXXX_add_logo_to_clinics.php
Schema::table('clinics', function (Blueprint $table) {
    $table->string('logo')->nullable()->after('cuit');
});
```

| Column | Type | Nullable | Index | Notes |
|--------|------|----------|-------|-------|
| `logo` | `varchar(255)` | yes | no | Stores filename only (`1_abc123.png`), not path |

**No index** — logo is fetched by primary key only (singleton clinic), never searched by filename.

## 5. Route Definition

```php
// Inside admin prefix group (already has auth.jwt middleware)
Route::post('/clinic/logo', UploadClinicLogoAction::class);

// Outside any middleware group — PUBLIC, no auth
Route::get('/logos/{filename}', ShowLogoAction::class)->name('logo.show');
```

**Why no `require_permissions` middleware**: permission is checked in `UploadClinicLogoCommand::execute()` via `PermissionService::ensure()`, consistent with `UpdateClinicCommand`.

## 6. Model Accessor

```php
// app/Models/Clinic.php
protected $fillable = [
    'nombre', 'direccion', 'telefono', 'email',
    'ciudad', 'provincia', 'codigo_postal', 'web', 'cuit',
    'logo',  // ← ADDED
];

protected $appends = ['logo_url'];  // ← ADDED

public function getLogoUrlAttribute(): ?string
{
    if (! $this->logo) {
        return null;
    }
    return route('logo.show', ['filename' => $this->logo]);
}
```

**Why `$appends`**: `logo_url` has no database column — Eloquent serialization requires it for JSON inclusion.

**Why `route()` instead of `Storage::url()`**: bypasses `storage:link` requirement. The public `GET /logos/{filename}` route returns `response()->file()` directly.

**No change to `GetClinicCommand` or `GetClinicAction`** — the accessor fires automatically when `Clinic` model is serialized to JSON.

## 7. Repository Method

```php
// app/Repositories/Clinic/ClinicSaveRepository.php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

public function updateLogo(UploadedFile $file): Clinic
{
    $clinic = Clinic::firstOrFail();

    // Delete old logo if exists
    if ($clinic->logo) {
        Storage::disk('public')->delete('logos/' . $clinic->logo);
    }

    $filename = $clinic->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    $file->storeAs('logos', $filename, 'public');

    $clinic->update(['logo' => $filename]);

    return $clinic->fresh();
}
```

## 8. File Storage

| Aspect | Value |
|--------|-------|
| Disk | `public` (`storage/app/public`) |
| Path pattern | `logos/{clinic_id}_{uniqid()}.{ext}` |
| URL generation | `route('logo.show', filename)` — no `storage:link` needed |
| Cleanup | Old file deleted via `Storage::disk('public')->delete()` before new store |
| Serving | `response()->file(storage_path('app/public/logos/' . $filename))` — correct MIME |

**Why `uniqid()` instead of timestamp**: microsecond collision safer than `time()` in concurrent requests.

## 9. Validation Rules

```php
$request->validate([
    'logo' => 'required|file|mimetypes:image/png,image/jpeg,image/svg+xml,image/webp|max:5120',
]);
```

| Rule | Value | Error Response |
|------|-------|---------------|
| `required` | — | 422 |
| `file` | Must be an uploaded file | 422 |
| `mimetypes` | `image/png`, `image/jpeg`, `image/svg+xml`, `image/webp` | 422 |
| `max` | 5120 KB (5 MB) | 422 |

**MIME validation via `mimetypes`** (not `mimes`) checks actual file content, not just extension — more secure. No `dimensions` rule: out of scope per proposal.

## 10. Error Handling

Exception flow map (consistent with `UpdateClinicAction` pattern):

| Scenario | Exception | HTTP Status | Where Caught |
|----------|-----------|-------------|-------------|
| No JWT token | `AuthenticationException` | 401 | `auth.jwt` middleware |
| JWT valid but no `admin.clinic.update` | `PermissionDeniedException` | 403 | `UploadClinicLogoAction` catch block |
| No clinic row exists | `ModelNotFoundException` | 404 | `UploadClinicLogoAction` catch block |
| Validation fails (bad MIME, oversized) | `ValidationException` | 422 | `UploadClinicLogoAction` catch block |
| File not found on `GET /logos/` | `Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException` | 404 | Laravel default |
| Any other runtime error | `\Exception` | 500 | `UploadClinicLogoAction` catch block (logged) |

**Action catch-block structure mirrors `UpdateClinicAction` exactly**:
```php
catch (ValidationException $e) → 422
catch (PermissionDeniedException $e) → 403
catch (ModelNotFoundException $e) → 404
catch (\Exception $e) → Log::error(...) + 500
```

## 11. Testing Strategy

```php
// tests/Feature/Admin/Clinic/ClinicLogoUploadTest.php
class ClinicLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    // Reuses existing helpers from ClinicSettingsTest:
    // - mockJwtForUserId(int $id)
    // - grantPermission(User $user, string $slug)
    // - authHeader(): array
}
```

| Test Case | What It Proves | Storage::fake |
|-----------|---------------|---------------|
| `test_upload_logo_returns_logo_url` | Happy path: valid PNG, 200, `logo_url` in response | yes |
| `test_upload_logo_requires_auth` | 401 without JWT token | no |
| `test_upload_logo_requires_permission` | 403 without `admin.clinic.update` | no |
| `test_upload_replaces_old_logo` | Old file deleted from disk, DB updated | yes (`assertMissing`) |
| `test_upload_rejects_invalid_mime` | 422 for `application/pdf` | no |
| `test_upload_rejects_oversized_file` | 422 for >5 MB file | no |
| `test_get_admin_clinic_includes_logo_url` | `GET /admin/clinic` response has `logo_url` (null when no logo) | no |
| `test_get_logos_serves_file` | Public `GET /logos/{filename}` returns file with correct Content-Type | yes |
| `test_get_logos_404_on_missing` | `GET /logos/nonexistent.png` returns 404 | no |

**`Storage::fake('public')`** is the key testing primitive:
```php
Storage::fake('public');
$response = $this->postJson('/admin/clinic/logo', [
    'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
], $this->authHeader());

Storage::disk('public')->assertExists('logos/' . Clinic::first()->logo);
```

**Mock strategy**: JWT mocked per existing pattern in `ClinicSettingsTest`. No other mocks needed — commands, repositories, and storage are tested through the HTTP layer.

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| Permission check location | In Command via `PermissionService::ensure()` | Route middleware | Consistent with `UpdateClinicCommand` pattern; single responsibility |
| Logo serving | Public route via `response()->file()` | `storage:link` + Nginx static files | Eliminates symlink dependency and CORS config; no perf concern for <5 MB images |
| URL generation | `route('logo.show', filename)` | `Storage::url()` / `asset()` | Bypasses `storage:link`; route helper always resolves correctly |
| File naming | `{clinic_id}_{uniqid()}.{ext}` | UUID / timestamp | `clinic_id` prefix for traceability; `uniqid()` for microsecond collision safety |
| Model accessor vs. Resource | `getLogoUrlAttribute()` via `$appends` | `ClinicResource` class | Simpler; zero changes to existing `GetClinicCommand`/`GetClinicAction` |
| Old file deletion | In `updateLogo()` before new store | Queue job / deferred | Atomic — if new store fails, no orphaned deletion; simple enough for synchronous |
| `$appends` for computed attribute | Included in model class | Dynamic via `JsonResource` | One-line change; transmitted automatically via all existing serialization paths |

## Open Questions

- [ ] **Route name collision**: `logo.show` — verify no existing named route uses `logo.*` prefix
- [ ] **Docker volume for `storage/app/public/logos/`**: verify the directory is persisted across container rebuilds (prod deploy concern, not blocking for design)
