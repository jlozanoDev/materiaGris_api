# Design: Sustitución de Variables del Sistema

## Technical Approach

Three independent capabilities, no cross-dependencies. Backend provides DATA only — the frontend's `SystemVariableRegistry` handles resolution. Follows existing hexagonal patterns: Action → Command → Repository, with exception mapping in Actions.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Clinic as singleton row (no IDs in URLs) vs CRUD | CRUD overkill for 1 record; `/admin/clinic/{id}` leaks no value | Singleton — `first()` or `create()` |
| Admin check in middleware vs Command | Middleware pattern (`require_permissions`) exists but `GET` needs NO check; `PUT` needs one | `PUT` uses `PermissionService::ensure('admin.access')` in Command (matches `SignReportCommand` pattern) |
| Repo for GET vs inline `Clinic::first()` | GET is one-liner; no persistence | Inline in `GetClinicCommand`. Repo only for `UpdateClinicCommand` |
| `GET /admin/clinic` returns 404 vs empty `{}` when no seeder ran | 404 is honest about missing data; empty `{}` masks broken seeder | 404 with `'message' => 'No clinic record found'` |

## Data Flow

```
Frontend (SystemVariableRegistry)
         │
         ▼ GET /admin/clinic (all users)    GET /admin/system-variables (catalog)
    ┌────┴────┐                            ┌──────────────┐
    │ GetClinic│                            │ GetSystemVar │
    │ Action   │                            │ Action       │
    └────┬────┘                            └──────┬───────┘
         │ Clinic::first()                         │ hardcoded array
         ▼                                         ▼
    {nombre, cuit, ...}                   [{clinica.cuit, ...}]

         PUT /admin/clinic (admin only)
    ┌────────────┐
    │UpdateClinic│──► PermissionService::ensure()
    │  Action    │──► Validate Request
    └─────┬──────┘──► ClinicSaveRepository::upsert()
          │
          ▼
    Clinic row (singleton)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/..._create_clinics_table.php` | Create | `clinics`: id, nombre, direccion, telefono, email, ciudad, provincia, codigo_postal, web, cuit, timestamps |
| `database/migrations/..._add_professional_fields_to_users.php` | Create | Add `apellido`, `num_colegiado`, `especialidad`, `telefono` (nullable strings) to `users` |
| `app/Models/Clinic.php` | Create | `$fillable` with all fields |
| `app/Models/User.php` | Modify | Add new fields to `$fillable` |
| `app/Repositories/Clinic/ClinicSaveRepository.php` | Create | `upsert(array $data): Clinic` — `first()` or `create()` |
| `app/Commands/Admin/Clinic/GetClinicCommand.php` | Create | `execute()` → `Clinic::first()` or throw 404 |
| `app/Commands/Admin/Clinic/UpdateClinicCommand.php` | Create | `execute(array $data)` → auth check → `PermissionService::ensure('admin.access')` → repo upsert |
| `app/Http/Actions/Admin/Clinic/GetClinicAction.php` | Create | Invokable, returns JSON. 404 → 404 with message. |
| `app/Http/Actions/Admin/Clinic/UpdateClinicAction.php` | Create | Validates Request, maps `PermissionDeniedException` → 403, `ValidationException` → 422, `\Exception` → 500 |
| `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` | Modify | Add `SystemVariable('clinica', 'cuit', ...)` |
| `app/Commands/Auth/MeCommand.php` | Modify | Add `apellido, num_colegiado, especialidad, telefono` to response array |
| `database/seeders/ClinicSeeder.php` | Create | Creates default Clinic row with placeholder values |
| `database/seeders/ReportTemplatesSeeder.php` | Modify | `{paciente.domicilio}` → `{paciente.direccion}` (3 occurrences) |
| `database/seeders/DatabaseSeeder.php` | Modify | Add `$this->call(ClinicSeeder::class)` |
| `database/factories/UserFactory.php` | Modify | Add faker values for new professional fields |
| `routes/api.php` | Modify | Add `GET /admin/clinic` and `PUT /admin/clinic` routes |

## Interfaces / Contracts

**Clinic model `$fillable`**:
```php
protected $fillable = [
    'nombre', 'direccion', 'telefono', 'email',
    'ciudad', 'provincia', 'codigo_postal', 'web', 'cuit',
];
```

**ClinicSaveRepository::upsert signature**:
```php
public function upsert(array $data): Clinic
```

**UpdateClinicCommand::execute signature**:
```php
public function execute(array $data): Clinic
```

**MeCommand response — new fields**:
```php
'apellido'      => $user->apellido,
'num_colegiado' => $user->num_colegiado,
'especialidad'  => $user->especialidad,
'telefono'      => $user->telefono,
```

**GET /admin/clinic response** (200):
```json
{
  "id": 1,
  "nombre": "Clínica Demo",
  "direccion": "Av. Siempre Viva 742",
  "telefono": "011-5555-0000",
  "email": "info@clinicademo.com",
  "ciudad": "Buenos Aires",
  "provincia": "CABA",
  "codigo_postal": "C1000",
  "web": "https://clinicademo.com",
  "cuit": "30-00000000-0"
}
```

**PUT /admin/clinic request** (JSON body — all fields optional):
```json
{
  "nombre": "Clínica Actualizada",
  "cuit": "30-11111111-1"
}
```

## Error Handling

| Exception | HTTP Status | Where Mapped |
|-----------|-------------|-------------|
| No clinic row (seeder not run) | 404 | `GetClinicAction` |
| `PermissionDeniedException` | 403 | `UpdateClinicAction` |
| `ValidationException` | 422 | `UpdateClinicAction` |
| Missing JWT | 401 | `auth.jwt` middleware |
| `\Exception` (unexpected) | 500 | `UpdateClinicAction` |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | `GET /admin/clinic` returns 200 with clinic data | Authenticate any user, assert JSON structure |
| Feature | `GET /admin/clinic` returns 404 when no seeder ran | Migration without seeder, assert 404 |
| Feature | `GET /admin/clinic` returns 401 without token | No auth header, assert 401 |
| Feature | `PUT /admin/clinic` returns 200 for admin | Admin JWT + valid data, assert DB updated |
| Feature | `PUT /admin/clinic` returns 403 for professional | Professional JWT, assert 403 |
| Feature | `PUT /admin/clinic` returns 422 on invalid data | Send missing/invalid fields |
| Feature | `GET /auth/me` includes new professional fields | Auth user, assert keys present |
| Feature | `GET /admin/system-variables` includes `clinica.cuit` | Assert key in response |
| Unit | `MeCommand` returns null for null fields | User without professional data |

## Migration / Rollout

No migration required. New columns are nullable — existing users unaffected. `ClinicSeeder` added to `DatabaseSeeder`. Rollback: `migrate:rollback --step=2`, delete Clinic* files.

## Open Questions

None — all design decisions resolved.
