# Design: GET /patients/{id}

## Technical Approach

Follow the hexagonal pattern from `GetUserAction` (single-resource lookup) combined with error handling from `GetPatientsAction`. Route → Middleware (JWT + permissions) → Action → Command → Repository → Model, all with constructor injection. No new migrations, config, or packages.

## Component Diagram

```
Request ──→ auth.jwt middleware ──→ require_permissions ──→ GetPatientAction
                 │                         │                    │
                 │ 401                     │ 403                ▼
                 ▼                         ▼              GetPatientCommand
            "Unauthorized"          "User lacks           │
                                    required permissions"  ├─ auth()->user()
                                                           ├─ PermissionService::ensure()
                                                           └─ PatientReadRepository::buscarPorId()
                                                                   │
                                                                   ▼
                                                              Patient::find(id)
```

## Architecture Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Action location | `App\Http\Actions\Patients\` | Matches `GetPatientsAction` sibling |
| Command namespace | `App\Commands\Admin\` | Follows `GetPatientsCommand` precedent |
| Repository method | `buscarPorId(int): ?Patient` | Exact signature from `GetUserRepository` |
| Double-gate: middleware + command | Both check `patient.view` | Defensive — middleware is first line, command provides defense-in-depth matching all existing commands |
| `last_visit_at` formatting | Action formats Carbon→`Y-m-d` before JSON | Spec requires date-only; keeping it in Action avoids model-side change |
| Response envelope | Direct JSON object (no `data` key) | Consistent with `GetUserAction` and `GetPatientsAction` |
| `buscarPorId` implementation | `Patient::find($id)` without eager-loading | Patient has no relationships to eager-load |

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `routes/api.php` | Modify | Add `GET /{id}` route at line 141 + import at line 20 |
| `app/Http/Actions/Patients/GetPatientAction.php` | Create | Invocable, delegates to command, handles 404/403/500 |
| `app/Commands/Admin/GetPatientCommand.php` | Create | Auth + permission guard, delegates to repository |
| `app/Repositories/Patient/PatientReadRepository.php` | Modify | Add `buscarPorId()` method |
| `tests/Feature/Patients/GetPatientTest.php` | Create | 4 scenarios: 200, 401, 403, 404 |

## Class Design

### GetPatientCommand
```php
// Constructor: property promotion matching GetPatientsCommand
public function __construct(
    private PatientReadRepository $leer,
    private PermissionService $permissionService,
) {}

// Signature: scalar int in, nullable model out
public function execute(int $id): ?Patient
```

### GetPatientAction
```php
// Constructor:
private GetPatientCommand $command;

// __invoke: try/catch wrapping execute()
public function __invoke(Request $request, $id): JsonResponse

// execute: null→404, formats last_visit_at→Y-m-d, returns json($patient)
public function execute(int $id): JsonResponse
```

### PatientReadRepository::buscarPorId
```php
public function buscarPorId(int $id): ?Patient
{
    return Patient::find($id);
}
```

## Data Flow

```
1. Route /patients/{id} matches, whereNumber validates integer
2. auth.jwt middleware: parseAndValidate JWT cookie → set auth user thread-local
3. require_permissions middleware: PermissionService::ensure(patient.view) → pass/403
4. GetPatientAction::__invoke((int)$id):
   a. GetPatientCommand::execute($id):
      - auth()->user() null? → throw PermissionDeniedException("Unauthorized")
      - PermissionService::ensure("patient.view") → pass/throw
      - PatientReadRepository::buscarPorId($id) → Patient|null
   b. null? → 404 "Paciente no encontrado"
   c. Format last_visit_at as Y-m-d via Carbon
   d. response()->json($patient) → 200
5. try/catch:
   - PermissionDeniedException → 403 (message from exception)
   - \Exception → Log::error + 500 "Internal server error"
```

## Error Handling

| Code | Source | Message |
|------|--------|---------|
| 401 | `AuthenticateJwt` middleware | `"Unauthorized"` |
| 403 | `RequirePermissions` middleware (1st gate) or Action catch (2nd gate) | `"User lacks required permissions"` |
| 404 | `GetPatientAction::execute()` | `"Paciente no encontrado"` |
| 500 | `GetPatientAction` catch-all | `"Internal server error"` |

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Feature | 200 valid patient | Create `Patient` via `::create([...])`, mock `JwtService` with anonymous token class (sub=user.id), grant `patient.view` permission, assert JSON has all fields + `age`/`full_name` accessors |
| Feature | 401 no auth | Omit `Authorization` header, assert 401 + `"Unauthorized"` |
| Feature | 403 no permission | Mock JWT but skip permission grant, assert 403 |
| Feature | 404 non-existent | Mock JWT + grant permission, call `/patients/99999`, assert 404 + `"Paciente no encontrado"` |

Test infrastructure follows `PatientsCrudTest`: `RefreshDatabase`, `fakeToken()` helper producing anonymous token class, `Permission::firstOrCreate` + `syncWithoutDetaching`, pass `Authorization: Bearer token123` header.

## Migration / Rollout

No migration required. Rollback: remove 1 route line + delete 3 class files + remove `buscarPorId` method — instant.

## Open Questions

- [ ] Spec specifies 401 message as `"No autenticado"` and 403 as `"No autorizado para ver este paciente"`, but middleware produces `"Unauthorized"` and `"User lacks required permissions"`. Align spec to middleware or customize messages? (Low priority — read-only endpoint, frontend can parse status codes)
