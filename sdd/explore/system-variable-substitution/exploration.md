## Exploration: System Variable Substitution Engine for Reports

**Date**: 2026-07-10
**Scope**: Full-system analysis of variable catalog, data sources, substitution points, admin patterns, and gap analysis.

---

### 1. Current State

#### 1.1 Variable Catalog (56 variables, 6 categories)

**File**: `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` (103 lines)

The catalog is a static, hardcoded array of `SystemVariable` DTOs returned by `GetSystemVariablesCommand::execute()`. Each variable has `category`, `key`, `label`, and `description`. The DTO is at `app/DTOs/SystemVariable.php` — a simple readonly class.

| Category | Count | Example |
|----------|-------|---------|
| `paciente` | 15 | `{paciente.nombre}`, `{paciente.edad}`, `{paciente.dni}` |
| `clinica` | 9 | `{clinica.nombre}`, `{clinica.direccion}`, `{clinica.telefono}` |
| `fecha` | 7 | `{fecha.actual}`, `{fecha.formato_largo}`, `{fecha.hora}` |
| `usuario` | 8 | `{usuario.nombre}`, `{usuario.matricula}`, `{usuario.especialidad}` |
| `medico` | 10 | `{medico.nombre}`, `{medico.matricula}`, `{medico.nro_colegiado}` |
| `informe` | 7 | `{informe.titulo}`, `{informe.fecha_creacion}`, `{informe.pagina_actual}` |

**Route**: `GET /admin/system-variables` → `GetSystemVariablesAction` → `GetSystemVariablesCommand` (line 137 of `routes/api.php`). Only requires `auth.jwt` — NO permission check (`require_permissions` middleware **not** applied). This is inconsistent with all other admin endpoints.

#### 1.2 How Variables Are Used in Templates

**File**: `database/seeders/ReportTemplatesSeeder.php` (1728 lines)

Three templates exist (HCG, Alta, Consentimiento). Variables appear in two field contexts:

**A) `fixed_text` fields** — `text_content` property contains raw text with embedded variables:
```json
{ "type": "fixed_text", "text_content": "{clinica.nombre}" }
{ "type": "fixed_text", "text_content": "CUIT: {clinica.cuit}" }
{ "type": "fixed_text", "text_content": "Edad: {paciente.edad} | Sexo: {paciente.sexo}" }
{ "type": "fixed_text", "text_content": "Médico tratante: {medico.nombre} — Mat. {medico.matricula}" }
{ "type": "fixed_text", "text_content": "Documento generado el {fecha.actual}" }
```

**B) `default_value` on regular fields** — auto-fill for text/select/number fields:
```json
{ "type": "text", "default_value": "{medico.nombre}" }
{ "type": "text", "default_value": "{paciente.nombre}" }
```

#### 1.3 Template Structure Snapshot in Reports

When a report is initialized (`InitReportCommand`), the template's `structure` JSON is copied to `patient_reports.template_structure_snapshot`. This snapshot retains the raw `{categoria.clave}` placeholders. It is NOT resolved at init time.

The report's `values` column stores the user-filled field values (keyed by field `key`), but these do NOT contain variables. The variables live only in the `template_structure_snapshot`.

#### 1.4 PDF Generation — Current State

**Package**: `barryvdh/laravel-dompdf: ^3.1` is in `composer.json` but **completely unused** across the entire codebase. Zero references to `PDF`, `dompdf`, or `Barryvdh` anywhere in `app/`.

**PDF view**: `resources/views/reports/pdf.blade.php` exists but only serves as a preview/draft renderer. It reads `values` and `template_structure_snapshot` directly but does NOT resolve variables. It renders the `{clinica.nombre}` placeholder as raw text in the HTML output.

**Archive flow**: `ArchiveReportCommand` expects the frontend to upload a pre-generated PDF (`?UploadedFile $pdfFile`). There is no server-side PDF rendering.

**Download flow**: `DownloadPdfReportCommand` checks that `pdf_path` exists in storage and serves it as a binary download. If the file is missing, it errors out.

**Sign flow**: `SignReportCommand` stores a base64 signature image but does NOT generate any PDF.

---

### 2. Affected Areas (files to touch)

| File | Role |
|------|------|
| `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` | Update catalog (add `cuit`, fix `domicilio` key) |
| `app/Services/` | New: `VariableResolverService` |
| `app/Models/` | New: `ClinicSetting` (or `InstitutionSetting`) |
| `database/migrations/` | New: `create_clinic_settings_table` |
| `app/Models/Patient.php` | Already has most needed fields; may need `peso`, `altura`, `grupo_sanguineo` |
| `app/Models/User.php` | Needs extension with professional fields (matricula, especialidad, telefono, etc.) |
| `app/Commands/Reports/ArchiveReportCommand.php` | Integrate PDF generation + variable resolution |
| `app/Commands/Reports/SignReportCommand.php` | Possibly generate PDF on sign |
| `resources/views/reports/pdf.blade.php` | Update to resolve variables |
| `routes/api.php` | Add admin CRUD routes for clinic settings |
| `app/Http/Actions/Admin/SystemVariable/GetSystemVariablesAction.php` | Add permission check |
| `database/seeders/ReportTemplatesSeeder.php` | Fix `{paciente.domicilio}` → `{paciente.direccion}` |

---

### 3. Data Source Analysis

#### 3.1 `paciente.*` — Patient Model

**Source**: `app/Models/Patient.php` + migrations

**Directly mappable (1:1)**:

| Variable Key | DB Column / Accessor | Status |
|-------------|---------------------|--------|
| `nombre` | `first_name` | ✅ |
| `apellido` | `last_name` | ✅ |
| `nombre_completo` | `full_name` accessor | ✅ |
| `edad` | `age` accessor | ✅ (computed from `date_of_birth`) |
| `sexo` | `gender` | ✅ |
| `nro_historia` | `medical_record_number` | ✅ |
| `dni` | `national_id` | ✅ |
| `fecha_nacimiento` | `date_of_birth` | ✅ |
| `direccion` | `address_line1` | ⚠️ partial (only line1; has line2, neighborhood, postal_code, state, country) |
| `telefono` | `phone` | ✅ |
| `email` | `email` | ✅ |

**Requires schema addition**:

| Variable Key | Missing | Notes |
|-------------|---------|-------|
| `peso` | No column in `patients` | Add `weight` (decimal) |
| `altura` | No column in `patients` | Add `height` (decimal) |
| `grupo_sanguineo` | No column in `patients` | Add `blood_type` (string) |
| `obra_social` | Only `insurance_id` (FK) | Need Insurance model or store name directly |

**CATALOG-SEEDER MISMATCH**: The seeder uses `{paciente.domicilio}` in all 3 templates (3 occurrences), but the catalog defines `paciente.direccion` (NOT `domicilio`). The seeder and catalog are out of sync. Also, `{paciente.nombre}` is misused in the Consentimiento template (line 1353) where it says "Procedimiento: {paciente.nombre}" — likely a template design error.

#### 3.2 `clinica.*` — No Data Source (MAIN GAP)

**Status**: ❌ No model, no table, no migration for clinic/institution data.

**Catalog variables that need data**:
- `nombre`, `direccion`, `telefono`, `email`, `ciudad`, `provincia`, `codigo_postal`, `web`, `logo`

**Template variables used but NOT in catalog**:
- `{clinica.cuit}` — used 6 times across all 3 templates but **missing from the 56-variable catalog**

**Design decision needed**: Single-row settings table (since there's only one clinic/institution) vs. multi-row `clinic_settings` key-value table.

#### 3.3 `fecha.*` — Computed (PHP)

**Status**: ✅ No data persistence needed. All 7 variables are computable:
- `actual` → `Carbon::now()->format('d/m/Y')`
- `formato_largo` → `Carbon::now()->isoFormat('D [de] MMMM [de] YYYY')`
- `corta` → `Carbon::now()->format('d/m/y')`
- `hora` → `Carbon::now()->format('H:i')`
- `fecha_hora` → `Carbon::now()->format('d/m/Y H:i')`
- `anio` → `Carbon::now()->format('Y')`
- `mes` → `Carbon::now()->isoFormat('MMMM')`

#### 3.4 `usuario.*` — User Model (SIGNIFICANT GAP)

**Source**: `app/Models/User.php` (Laravel default)

**Available fields**: `name`, `email`, `password`, `email_verified_at`. Single `name` field (not split into first/last).

**Missing for catalog**:

| Variable Key | Available? | Notes |
|-------------|-----------|-------|
| `nombre` | ❌ | `name` contains full name, need split |
| `apellido` | ❌ | Same as above |
| `nombre_completo` | ⚠️ | Could use `name` directly |
| `matricula` | ❌ | No column |
| `email` | ✅ | Exists |
| `especialidad` | ❌ | No column |
| `rol` | ⚠️ | Exists via roles relationship but not a single string |
| `telefono` | ❌ | No column |

**Note**: The `usuario.*` category is **never used in any template** (0 occurrences in the seeder). The templates exclusively use `{medico.*}` for the professional's name. This category may be redundant with `medico`.

#### 3.5 `medico.*` — User Model (SIGNIFICANT GAP)

**Source**: `app/Models/User.php` — same model as usuario.

**Used in every template** — 11 occurrences across the 3 templates (the most-used category after clinica and paciente).

| Variable Key | Available? | Notes |
|-------------|-----------|-------|
| `nombre` | ❌ | Need `first_name` on User |
| `apellido` | ❌ | Need `last_name` on User |
| `nombre_completo` | ⚠️ | Could use `name` directly |
| `matricula` | ❌ | No column |
| `especialidad` | ❌ | No column |
| `email` | ✅ | Exists |
| `telefono` | ❌ | No column |
| `dias_consulta` | ❌ | No column |
| `horario` | ❌ | No column |
| `nro_colegiado` | ❌ | No column |

**Design decision**: Should `usuario` and `medico` resolve to the same source (the authenticated User who creates the report)? The names suggest different roles — `usuario` is the "professional generating the report" and `medico` is the "treating physician." In small practice, these are often the SAME person. However, in larger institutions, the `medico` might be someone different. Only `usuario.*` is NEVER used in templates — all templates reference `{medico.*}`.

**Recommendation**: Merge `usuario` and `medico` into one data source (the authenticated User model extended with professional fields). If a future use case needs a separate physician model, that can be added later.

#### 3.6 `informe.*` — Computed from Report Context

**Status**: ✅ Computable from the `PatientReport` and its relationships.

| Variable Key | Source |
|-------------|--------|
| `titulo` | `$report->template->name` |
| `tipo` | Could be `$report->template->name` or a new type field |
| `fecha_creacion` | `$report->created_at` |
| `fecha_firma` | `$report->signed_at` |
| `pagina_actual` | PDF page counter (only meaningful during PDF generation) |
| `pagina_total` | PDF page counter |
| `pagina_actual_de_total` | PDF page counter |

**Note**: `{informe.*}` is **never used in any template** (0 occurrences), though the catalog has 7 variables. These would be useful in footers.

---

### 4. Where Substitution SHOULD Happen

Substitution has two contexts with different timing:

#### 4.1 At Template Preview / Form Render Time

When the frontend loads a report form, it should resolve `default_value` variables:
- `{paciente.nombre}` → actual patient name in the form field
- `{medico.nombre}` → current user's name

This happens at **GET /reports/{id}** (form view), not at PDF generation time. The resolver receives the report ID, fetches patient + user data, and replaces variables in `default_value` fields on the fly.

#### 4.2 At PDF Generation Time (Main Use Case)

When generating the PDF (at archive or download time), ALL variables in `fixed_text.text_content` AND `default_value` fields must be resolved. This is where the full resolver engine is needed:

1. Load the report with patient and user relationships
2. Load clinic settings from DB
3. Walk the `template_structure_snapshot` recursively
4. For every `fixed_text` field: resolve `{categoria.clave}` patterns in `text_content`
5. For every regular field: resolve `{categoria.clave}` in `default_value` (if not already filled by user)
6. Render the Blade view with resolved values

#### 4.3 PDF Generation Pipeline (Proposed Integration Point)

The most natural integration point is the **sign → archive** flow:

```
SignReportCommand (current: stores signature image only)
    ↓
  [NEW: Generate PDF with resolved variables]
    ↓
ArchiveReportCommand (current: expects uploaded PDF)
    ↓
  [MODIFY: Use generated PDF path instead of uploaded file]
```

Alternative: Generate PDF on **sign** (not archive), making PDF available immediately after signing without requiring a separate archive step.

---

### 5. Existing Admin Patterns

#### 5.1 Permission System

**Files**: `app/Services/PermissionService.php`, `app/Http/Middleware/RequirePermissions.php`

Pattern: Each admin module has CRUD permissions like `admin.reporttemplate.{view|create|update|delete}`. The `require_permissions` middleware checks these. Permission checks use slug-based granular permissions with grant/deny model.

#### 5.2 Admin CRUD Pattern (ReportTemplate as reference)

**Consistent layered pattern**:

```
Route (routes/api.php)
  → Action (app/Http/Actions/Admin/ReportTemplate/CreateReportTemplateAction.php)
    → FormRequest validation (app/Http/Requests/Admin/...)
    → Command (app/Commands/Admin/ReportTemplate/CreateReportTemplateCommand.php)
      → PermissionService::ensure()
      → Repository (app/Repositories/ReportTemplate/ReportTemplateSaveRepository.php)
        → Eloquent Model
```

**For clinic settings, the same pattern would be**:

```
GET/PUT /admin/clinic-settings
  → ClinicSettingsAction
    → GetClinicSettingsCommand / UpdateClinicSettingsCommand
      → PermissionService::ensure('admin.clinic-settings.manage')
      → ClinicSettingsRepository
        → ClinicSetting model
```

#### 5.3 Route Group Structure

All admin routes are under `Route::prefix('admin')->middleware('auth.jwt')` (line 71 of `routes/api.php`). Individual routes add `require_permissions:*` middleware. The system-variables endpoint is the ONLY admin route without a permission check — should be fixed.

---

### 6. Gap Analysis

#### 6.1 Critical — What Blocks Substitution

| Gap | Impact | Effort |
|-----|--------|--------|
| **No clinic data source** | `{clinica.*}` resolves to empty/null — broken headers on ALL reports | **High** — needs migration + model + admin UI endpoints + seeder |
| **No variable resolver service** | Template placeholders render as raw `{clinica.nombre}` text | **Medium** — regex-based resolver + per-category resolution strategies |
| **No PDF generation pipeline** | No server-side PDF rendering. Dompdf installed but unused. | **Medium** — Blade → HTML → Dompdf pipeline |
| **User model lacks professional fields** | `{medico.matricula}`, `{medico.especialidad}`, etc. can't resolve | **High** — migration to extend users table + update register/profile UI |
| **Patient model lacks clinical fields** | `{paciente.peso}`, `{paciente.altura}`, `{paciente.grupo_sanguineo}` can't resolve | **Medium** — migration to extend patients table |

#### 6.2 Important — Data Quality / Consistency

| Gap | Impact | Effort |
|-----|--------|--------|
| **`{clinica.cuit}` missing from catalog** | Templates reference `{clinica.cuit}` 6 times but it's not in the 56-variable catalog → frontend autocomplete won't show it | **Low** — add 1 line to `GetSystemVariablesCommand` |
| **`{paciente.domicilio}` vs `paciente.direccion`** | Seeder uses `domicilio`, catalog defines `direccion`. Key mismatch = variable won't resolve | **Low** — align naming (pick one) |
| **`{usuario.*}` never used, `{medico.*}` heavily used** | Catalog defines 8 `usuario` variables but 0 template uses. All professional references use `{medico.*}` | **Low** — decision needed on merge vs. keep separate |
| **No `require_permissions` on system-variables endpoint** | Inconsistent with every other admin endpoint. Currently only `auth.jwt` | **Low** — add middleware |

#### 6.3 Nice-to-Have — Future Improvements

| Gap | Impact | Effort |
|-----|--------|--------|
| **Patient address resolved per component** | `paciente.direccion` only maps to `address_line1` but patient has full address (line2, neighborhood, state, postal_code, country) | **Low** — add separate variables for each address component |
| **`paciente.obra_social` needs insurance name** | Patient has `insurance_id` FK but no Insurance model exists. `obra_social` variable would resolve to NULL or an ID number | **Medium** — needs ObraSocial/Insurance model + seeder |
| **`informe.*` page variables only work in PDF** | `pagina_actual` / `pagina_total` only meaningful during multi-page PDF rendering | **Low** — implement as PDF footer only |

---

### 7. Recommended Implementation Approach

#### Architecture: Strategy Pattern Resolver

```
VariableResolverService::resolve(string $text, ResolutionContext $ctx): string
```

The `ResolutionContext` carries the contextual objects needed at resolution time:
```php
class ResolutionContext {
    public ?Patient $patient;
    public ?User $user;       // for both medico and usuario
    public ?PatientReport $report;
    public ?ClinicSetting $clinic;
}
```

Each category gets a dedicated resolver strategy implementing a common interface:
```
VariableResolver (interface)
├── PatientVariableResolver (maps paciente.* keys → Patient attributes)
├── ClinicVariableResolver (maps clinica.* keys → ClinicSetting attributes)
├── DateVariableResolver (maps fecha.* keys → Carbon formatting)
├── UserVariableResolver (maps usuario.* and medico.* keys → User attributes)
└── ReportVariableResolver (maps informe.* keys → PatientReport context)
```

#### Implementation Order (Recommended Phases)

**Phase 1 — Foundation (Enables `clinica.*` and `medico.*` immediately)**
1. Create `clinic_settings` migration + `ClinicSetting` model
2. Seed with default/placeholder values
3. Create `UpdateClinicSettingsCommand` + admin endpoints (PUT `/admin/clinic-settings`)
4. Add permissions: `admin.clinic-settings.view`, `admin.clinic-settings.update`
5. Extend `users` migration: add `first_name`, `last_name`, `license_number` (matricula), `specialty`, `phone`, `collegiate_number`
6. Fix catalog gaps: add `{clinica.cuit}`, align `domicilio`→`direccion`

**Phase 2 — Resolver Engine**
7. Create `VariableResolverService` with strategy pattern
8. Build per-category resolvers
9. Create `ResolutionContext` DTO
10. Unit tests for each resolver

**Phase 3 — Integration**
11. Update `pdf.blade.php` to call resolver before rendering
12. Create PDF generation pipeline in `SignReportCommand` or a new `GeneratePdfCommand`
13. Update `ArchiveReportCommand` to use server-generated PDF (or remove upload requirement)
14. Resolve `default_value` variables at report form render time

**Phase 4 — Polish**
15. Add `require_permissions` to system-variables endpoint
16. Update seeder to use consistent variable keys
17. Add patient clinical fields (`weight`, `height`, `blood_type`) if needed in current templates
18. Add `ObraSocial`/`Insurance` model for `paciente.obra_social`

---

### 8. Risks

1. **User model changes are breaking**: Adding columns to `users` table affects registration, profile edit, and JWT auth. Need to update `CreateUserCommand`, `UpdateUserCommand`, `MeAction`, related tests, and possibly the frontend.

2. **`medico` vs `usuario` ambiguity**: If templates reference both `{medico.nombre}` and `{usuario.nombre}`, and both resolve to the SAME user, that's fine. But if a future template references both with the expectation they differ, the merge approach breaks. **Decision**: merge now, split later only if needed.

3. **PDF generation**: Dompdf has limitations with complex CSS layouts. The current `pdf.blade.php` uses simple tables — this should work fine, but needs testing with the actual template structures (nested sections/rows/columns).

4. **`template_structure_snapshot` is a point-in-time copy**: If variables are resolved at PDF time against LIVE data, there's a mismatch between what the user saw when filling the form and what appears in the PDF. **Mitigation**: resolve `default_value` at form render time and `fixed_text` at PDF generation time.

5. **Page number variables require multi-pass rendering**: `{informe.pagina_actual}` and `{informe.pagina_total}` require two-pass PDF generation (or Dompdf's built-in page counter). This is a known complexity with PDF engines.

---

### 9. Ready for Proposal

**Yes** — all necessary information has been collected. The proposal should address:

1. **Clinic settings model/table** — admin-managed, single-row or key-value
2. **User model extension** — professional fields for both `medico` and `usuario` resolution
3. **Variable resolver service** — strategy pattern, resolves `{categoria.clave}` across all 6 categories
4. **PDF generation pipeline** — integrate dompdf with variable-resolved Blade rendering
5. **Admin endpoints** for clinic settings management (CRUD with proper permissions)
6. **Catalog fixes** — `cuit` addition, `domicilio`/`direccion` alignment
7. **Patient model gaps** — if `peso`/`altura`/`grupo_sanguineo`/`obra_social` are needed now or deferred
