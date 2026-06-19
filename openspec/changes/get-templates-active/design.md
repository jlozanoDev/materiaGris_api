# Design: GET /api/templates/active

## Technical Approach

Hexagonal pattern: **Action → Command → Repository → Model**. New read-only endpoint in `App\Http\Actions\Reports\` and `App\Commands\Reports\` — co-located with the report creation flow (`InitReportCommand`, `ListReportsCommand`). Adds `listarActivas(): Collection` to the existing `ReportTemplateReadRepository`. No pagination, no request validation, no DB changes. Permission: `report.create` (already seeded).

## Architecture Decisions

| # | Decision | Choice | Alternatives | Rationale |
|---|----------|--------|-------------|-----------|
| 1 | Namespace | `Reports` (not Admin) | Admin `ReportTemplate` namespace | Endpoint serves report creation flow; uses `report.create`, not admin permission; co-locates with `InitReportCommand`, `ListReportsCommand` |
| 2 | JSON keys | snake_case | camelCase (task template) | All existing endpoints return snake_case (`patient_id`, `created_at`). CamelCase would break frontend consistency. |
| 3 | Pagination | Flat `Collection`, no `meta` | Reuse `listar()` with paginator | Active templates are few (<50); frontend needs all for picker. Response envelope: `{ data: [...] }` only. |
| 4 | Repository | New `listarActivas()` method | Reuse `listar(['is_active'=>true])` | Self-documenting, returns exact type, avoids paginator overhead. Hexagonal principle: new use case → new query method. |
| 5 | Permission | Double-gate (middleware + Command) | Middleware-only | Established project pattern — `ListReportsCommand` and `ListReportTemplatesCommand` both re-check inside `execute()`. Defense-in-depth. |

## Data Flow

```
GET /api/templates/active
  │
  ├─ middleware: auth.jwt              → validates JWT from cookie
  ├─ middleware: require_permissions   → checks report.create (gate 1)
  │   :report.create
  │
  └─ GetActiveTemplatesAction::__invoke(Request $request): JsonResponse
       │
       └─ try
            GetActiveTemplatesCommand::execute(): Collection
              ├─ auth()->user() → PermissionDeniedException('Unauthorized') if null
              ├─ PermissionService::ensure($user, 'report.create')  (gate 2)
              └─ ReportTemplateReadRepository::listarActivas()
                   └─ ReportTemplate::where('is_active', true)
                        ->orderBy('name')->get()
            │
            → response()->json(['data' => $collection], 200)
          catch PermissionDeniedException → 403
          catch Exception → 500 + Log::error
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Repositories/ReportTemplate/ReportTemplateReadRepository.php` | Modify | Add `listarActivas(): Collection` — `ReportTemplate::where('is_active', true)->orderBy('name')->get()` |
| `app/Commands/Reports/GetActiveTemplatesCommand.php` | Create | Auth guard + `PermissionService::ensure()` + repo call; ~30 lines |
| `app/Http/Actions/Reports/GetActiveTemplatesAction.php` | Create | Invocable with try/catch (403/500); ~35 lines |
| `routes/api.php` | Modify | Add `use` import + `templates` prefix group after reports group (~line 166) |
| `tests/Feature/Actions/Reports/GetActiveTemplatesTest.php` | Create | 4 feature tests following `ReportsCrudTest` pattern; ~60 lines |

## Interfaces / Contracts

**ReportTemplateReadRepository::listarActivas()**
```php
public function listarActivas(): \Illuminate\Support\Collection
{
    return ReportTemplate::where('is_active', true)
        ->orderBy('name')
        ->get();
}
```
Soft-deleted templates excluded automatically via `SoftDeletes` global scope. `structure` is included (frontend `ReportFillPage` needs it). `is_active` cast to boolean by model.

**GetActiveTemplatesCommand::execute()**
```php
public function execute(): \Illuminate\Support\Collection
```
Throws `PermissionDeniedException` if `auth()->user()` is null or user lacks `report.create`. Returns `Collection<ReportTemplate>`.

**Response shape (200)**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Informe general",
      "description": "Plantilla para informes médicos generales",
      "is_active": true,
      "structure": { "sections": [...] },
      "created_at": "2026-06-19T17:00:00.000000Z",
      "updated_at": "2026-06-19T17:00:00.000000Z"
    }
  ]
}
```
No `meta` key. `deleted_at` excluded by `SoftDeletes` (hidden when null). Keys are snake_case matching Laravel model serialization default.

## Route Registration

In `routes/api.php`, after the `reports` group (after line 166):
```php
use App\Http\Actions\Reports\GetActiveTemplatesAction;

// Templates routes - protected by JWT
Route::prefix('templates')->middleware('auth.jwt')->group(function () {
    Route::get('/active', GetActiveTemplatesAction::class)
        ->middleware('require_permissions:report.create');
});
```

## Error Handling

| Exception | HTTP | Message |
|-----------|------|---------|
| `PermissionDeniedException` (no JWT) | 403 | `"Unauthorized"` |
| `PermissionDeniedException` (no permission) | 403 | `"User lacks required permissions"` |
| Generic `\Exception` | 500 | `"Internal server error"` + `Log::error()` |

Middleware-level failures (invalid/missing JWT → 401, missing permission → 403) fire before the Action. The Action's try/catch handles Command-level exceptions.

## Testing Strategy

All 4 tests follow `ReportsCrudTest` pattern: `RefreshDatabase`, private helpers (`mockJwtForUserId`, `grantPermission`, `actingWithPermission`, `authHeader`), `ReportTemplate::factory()`.

| Test | Setup | Assert |
|------|-------|--------|
| `test_returns_active_templates` | 3 active + 1 inactive + 1 soft-deleted templates, user with `report.create` | 200, count=3, assertJsonStructure for `data.*` keys |
| `test_returns_empty_when_no_active` | 0 active templates, user with `report.create` | 200, `data` is empty array |
| `test_returns_401_when_unauthenticated` | No JWT mock | 401 (middleware-level) |
| `test_returns_403_when_no_permission` | JWT user without `report.create` | 403 (middleware-level) |

## Migration / Rollout

No migration required. Instant rollback: remove route + delete `GetActiveTemplatesAction.php` + `GetActiveTemplatesCommand.php` + remove `listarActivas()` from repository.

## Open Questions

None — all infrastructure exists (`report.create` seeded, `ReportTemplate` model/factory, `ReportTemplateReadRepository`, JWT middleware, `PermissionService`).
