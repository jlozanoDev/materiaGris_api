# Tasks: Sustitución de Variables del Sistema

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~370 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full change | PR 1 | All 3 capabilities fit in ~370 lines |

---

## Phase 1: Clinic Settings (Capability 1) — ~320 lines

> Files: 8 new + 1 modified. All auth'd users can GET; admin guard on PUT.
> Admin gate: `PermissionService::ensure($user, 'admin.user.view')` (existing slug).

- [x] 1.1 **Migration** — `database/migrations/..._create_clinics_table.php`  
  Columns: `id, nombre, direccion, telefono, email, ciudad, provincia, codigo_postal, web, cuit, timestamps`  
  ~35 lines | No deps

- [x] 1.2 **Model** — `app/Models/Clinic.php`  
  `$fillable` with all columns, no relations (singleton)  
  ~20 lines | Deps: 1.1

- [x] 1.3 **Seeder** — `database/seeders/ClinicSeeder.php`  
  Creates one default row with sensible placeholder values  
  ~25 lines | Deps: 1.1

- [x] 1.4 **Repository** — `app/Repositories/Clinic/ClinicSaveRepository.php`  
  `getOrFail()` (returns first row or 404), `update(array $data)`  
  ~20 lines | Deps: 1.2

- [x] 1.5 **Command** — `app/Commands/Admin/Clinic/GetClinicCommand.php`  
  Injects repo, calls `getOrFail()`, no auth check (delegated to middleware/action)  
  ~22 lines | Deps: 1.4

- [x] 1.6 **Command** — `app/Commands/Admin/Clinic/UpdateClinicCommand.php`  
  Auth check (`auth()->user()`), `PermissionService::ensure($user, 'admin.user.view')`, calls `repo->update($data)`  
  ~30 lines | Deps: 1.4

- [x] 1.7 **Action** — `app/Http/Actions/Admin/Clinic/GetClinicAction.php`  
  Injects `GetClinicCommand`, maps exceptions, `__invoke()` returns JSON  
  ~22 lines | Deps: 1.5

- [x] 1.8 **Action** — `app/Http/Actions/Admin/Clinic/UpdateClinicAction.php`  
  Injects `UpdateClinicCommand`, validates request, maps exceptions, `__invoke()` returns JSON  
  ~40 lines | Deps: 1.6

- [x] 1.9 **Routes** — `routes/api.php`  
  Add `GET /admin/clinic` (no permission middleware) and `PUT /admin/clinic` inside admin group + imports  
  ~8 lines | Deps: 1.7, 1.8

- [x] 1.10 **Tests** — `tests/Feature/Admin/Clinic/ClinicSettingsTest.php`  
  5 cases: (1) GET returns clinic, (2) GET without auth → 401, (3) PUT updates clinic, (4) PUT without auth → 401, (5) PUT with non-admin → 403  
  ~100 lines | Deps: 1.9

---

## Phase 2: User Professional Fields (Capability 2) — ~35 lines

> Independent of C1 and C3 — can run in parallel. Adds columns to users + exposes them in `/auth/me`.

- [x] 2.1 **Migration** — `database/migrations/..._add_professional_fields_to_users.php`  
  `apellido, num_colegiado, especialidad, telefono` (nullable string)  
  ~22 lines | No deps

- [x] 2.2 **Model** — `app/Models/User.php`  
  Add `apellido, num_colegiado, especialidad, telefono` to `$fillable`  
  ~5 lines | Deps: 2.1

- [x] 2.3 **MeCommand** — `app/Commands/Auth/MeCommand.php`  
  Include `apellido, num_colegiado, especialidad, telefono` in the `/me` response array  
  ~10 lines | Deps: 2.2

---

## Phase 3: System Variables Catalog Fix (Capability 3) — ~8 lines

> Independent of C1 and C2 — can run in parallel. Adds `cuit` variable + fixes `domicilio` → `direccion`.

- [x] 3.1 **Command** — `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php`  
  Add `new SystemVariable('clinica', 'cuit', 'CUIT', 'CUIT de la clínica')` after `codigo_postal` line  
  ~5 lines | No deps

- [x] 3.2 **Seeder** — `database/seeders/ReportTemplatesSeeder.php`  
  Fix 3 instances: `{paciente.domicilio}` → `{paciente.direccion}` (lines 185, 832, 1324)  
  ~3 lines | No deps
