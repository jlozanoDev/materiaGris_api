## Exploration: get-templates-active

### Current State

The ReportTemplate module is **fully established** with model, migration, repositories, admin CRUD, factories, and tests:

**Database (`report_templates`):**
- `id` (bigint auto-increment), `name` (string), `description` (text, nullable), `is_active` (boolean, default true), `structure` (JSON), `timestamps`, `softDeletes`
- Migration: `2026_06_09_000001_create_report_templates_table.php`

**Model (`App\Models\ReportTemplate`):**
- Uses `SoftDeletes` trait
- Casts: `structure => array`, `is_active => boolean`
- Fillable: `name`, `description`, `is_active`, `structure`

**Existing Admin Endpoints (`/admin/report-templates`):**
- `GET /admin/report-templates` — `ListReportTemplatesAction` (paginated, with filters `is_active`, `q`, `per_page`)
- `POST /admin/report-templates` — `CreateReportTemplateAction`
- `GET /admin/report-templates/{id}` — `GetReportTemplateAction` (returns single model, no `data` wrapper)
- `PUT /admin/report-templates/{id}` — `UpdateReportTemplateAction`
- `DELETE /admin/report-templates/{id}` — `DeleteReportTemplateAction`
- All use `require_permissions:admin.reporttemplate.{view|create|update|delete}`

**Repository (`ReportTemplateReadRepository`):**
- `listar(array $filters)`: Supports `is_active` filter (boolean), `q` search (name/description LIKE), pagination (`per_page`, default 15). Returns `LengthAwarePaginator`.
- `buscarPorId(int $id): ?ReportTemplate`: Simple `find($id)`.

**No existing public (non-admin) template endpoint exists.** The only way to list templates currently is via the admin panel.

---

### Affected Areas

| Area | File | Impact |
|------|------|--------|
| Routes | `routes/api.php` | New route group `templates` + route `GET /active` + import |
| HTTP Action | `app/Http/Actions/Reports/GetActiveTemplatesAction.php` | **New file** |
| Command | `app/Commands/Reports/GetActiveTemplatesCommand.php` | **New file** |
| Repository | `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` | New method `listarActivas()` |
| Tests | `tests/Feature/Actions/Reports/GetActiveTemplatesTest.php` | **New file** |

**Why Reports namespace?** The endpoint serves the report creation flow (professionals pick a template before calling `POST /reports`). The permission used is `report.create`, not an admin permission. Placing Action+Command alongside other Reports commands maintains cohesion with the use case (InitReportCommand, ListReportsCommand, etc.).

---

### Approaches

**1. Minimal: Reuse `listar()` with `is_active=true` filter (no new repository method)**

Create Action → Command that calls `$repo->listar(['is_active' => true, 'per_page' => 100])` and strips pagination meta.

- Pros: Zero repository changes, fastest to implement
- Cons: Leaky abstraction — uses pagination infrastructure for a non-paginated endpoint; `per_page=100` is a magic number; response still wraps in paginator
- Effort: Low

**2. Add `listarActivas()` to repository (recommended)**

Add a dedicated method `listarActivas(): \Illuminate\Support\Collection` that queries `ReportTemplate::where('is_active', true)->orderBy('name')->get()`.

- Pros: Clean, self-documenting, returns exactly what the endpoint needs (no pagination overhead), follows hexagonal pattern (new query = new repository method)
- Cons: One small repository change
- Effort: Low

**3. Reuse admin ListReportTemplatesAction with different permission**

Route the public endpoint to the existing `ListReportTemplatesAction` but with `require_permissions:report.create` middleware instead of `admin.reporttemplate.view`.

- Pros: One less Action file
- Cons: The admin Action returns paginated data with `meta` — doesn't match the spec (no pagination); mixes admin and public concerns; the internal Command checks `admin.reporttemplate.view` in addition to middleware

**→ Recommendation: Approach 2 (add `listarActivas()`)**

The endpoint spec explicitly shows `"data": [...]` without pagination metadata. Approach 2 is the cleanest fit for the hexagonal architecture: new use case → new repository method that expresses the exact query intent.

---

### Recommendation

**Approach 2** with the following concrete plan:

1. **Repository** — Add `listarActivas(): Collection` to `ReportTemplateReadRepository`:
   ```php
   public function listarActivas(): \Illuminate\Support\Collection
   {
       return ReportTemplate::where('is_active', true)
           ->orderBy('name')
           ->get();
   }
   ```

2. **Command** — `GetActiveTemplatesCommand` in `app/Commands/Reports/`:
   - Pattern: identical to `ListReportTemplatesCommand` / `InitReportCommand`
   - Auth guard: `auth()->user()` check → PermissionDeniedException('Unauthorized')
   - Permission: `$this->permissionService->ensure($user, 'report.create')`
   - Call: `$this->repo->listarActivas()`
   - Return: `Collection` of `ReportTemplate` models

3. **Action** — `GetActiveTemplatesAction` in `app/Http/Actions/Reports/`:
   - Constructor injection of `GetActiveTemplatesCommand`
   - `__invoke(Request $request): JsonResponse` (no path params, no request validation needed)
   - Try/catch: `PermissionDeniedException` → 403, general `Exception` → 500 with logging
   - Response shape: `response()->json(['data' => $templates])` (Collection serializes to array of objects)
   - No pagination wrapper — the spec shows only `"data"`

4. **Route** — New group in `routes/api.php`:
   ```php
   Route::prefix('templates')->middleware('auth.jwt')->group(function () {
       Route::get('/active', GetActiveTemplatesAction::class)
           ->middleware('require_permissions:report.create');
   });
   ```
   Place it after the `reports` group (convention: related resources grouped together).

5. **Tests** — 4 feature tests following `ReportTemplateCrudTest` pattern:
   - `test_returns_active_templates` — creates 3 active + 1 inactive + 1 soft-deleted → asserts 3 in response, correct structure
   - `test_returns_empty_when_no_active_templates` — asserts 200 with empty `data` array
   - `test_unauthenticated_returns_401` — no JWT user → 401 (middleware-level)
   - `test_unauthorized_returns_403` — JWT user without `report.create` → 403

---

### Risks

- **`structure` field size**: Some templates may have large JSON structures (many sections, rows, columns). Consider `select()` excluding `structure` if the picker doesn't need it, but the spec says the frontend uses `structure` in `ReportFillPage`, so it must be included.
- **Duplicate permission check**: The middleware checks `report.create` AND the Command also calls `ensure('report.create')`. This is the established project pattern (double-gate), not a bug — but it means a bypass of one layer still fails at the other.
- **`id` type mismatch**: DB returns int, frontend TypeScript expects `string`. This is handled by JSON parsing (JavaScript numbers). No action needed — existing admin endpoints have the same behavior and it works.
- **Soft-deleted templates**: The `SoftDeletes` trait means `find()` won't return deleted models, and `all()` query builder `where('is_active', true)` on `ReportTemplate::query()` also respects soft-deletes (Laravel's `SoftDeletes` adds a global scope). Soft-deleted templates are correctly excluded.
- **No `config.yaml` in openspec**: The `openspec/` directory has no config file yet. The exploration artifact is written to `openspec/changes/get-templates-active/explore.md` per convention.

---

### Complexity: Low

**Estimated lines changed: ~130 total**

| File | Lines | Type |
|------|-------|------|
| `routes/api.php` | +4 (import), +4 (group+route) | Modify |
| `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` | +7 (method) | Modify |
| `app/Commands/Reports/GetActiveTemplatesCommand.php` | ~30 | New |
| `app/Http/Actions/Reports/GetActiveTemplatesAction.php` | ~35 | New |
| `tests/Feature/Actions/Reports/GetActiveTemplatesTest.php` | ~50 | New |

No migrations, no config changes, no package additions.

---

### Ready for Proposal

**Yes** — all infrastructure exists. The `report.create` permission is already seeded. The repository already has filtering infrastructure. The endpoint is a thin read-only wrapper. Proceed to `sdd-propose`.
