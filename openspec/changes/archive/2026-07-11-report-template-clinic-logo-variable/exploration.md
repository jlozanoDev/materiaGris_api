## Exploration: Add `{clinica.logo}` Variable Above Clinic Name in Report Templates Seeder

**Date**: 2026-07-11
**Change name**: `report-template-clinic-logo-variable`
**Scope**: Seeder-only change — add a `fixed_text` field with `{clinica.logo}` above `{clinica.nombre}` in all 3 seed templates.

---

### 1. Executive Summary

The `{clinica.logo}` system variable is already **fully wired** — it exists in the variable catalog, the `clinics` table has a `logo` column, and there's a public serving endpoint (`GET /logos/{filename}`). However, **none of the 3 seed templates include it** in their header structure. This change adds it to all three templates as a `fixed_text` field positioned **above** the clinic name, within the clinic info column of the header.

**Effort estimate**: Trivial (single-file change, ~60 lines of JSON addition).
**Risk**: None. No model changes, no migrations, no new variable registration.

---

### 2. Current State

#### 2.1 Template Seeder (`database/seeders/ReportTemplatesSeeder.php`)

Three templates are seeded:

| Template | Name (Spanish) | Lines |
|----------|----------------|-------|
| `hcgTemplate()` | Historia Clínica General | 44–689 |
| `iaTemplate()` | Informe de Alta | 691–1181 |
| `ciTemplate()` | Consentimiento Informado | 1183–1727 |

Each template has an identical structural skeleton:
```
structure
├── header
│   ├── enabled: true, pageDisplay: "all"
│   └── sections[0] → "Encabezado"
│       └── rows[]
│           ├── row-1: title (e.g., "Historia Clínica General")
│           ├── row-2: two-column layout (clinic info | patient info)
│           └── row-3/4: doctor info + date
├── sections[] → body content (diagnosis, exam, etc.)
└── footer
    ├── enabled: true, pageDisplay: "all"
    └── sections[0] → signature/fecha
```

#### 2.2 The Clinic Info Column (row 2, column 2a)

In **all three templates**, the clinic info column (`*-header-col-2a`) currently has **4 fields** in this order:

1. `{clinica.nombre}` — `fixed_text`, styled `bold: true, size: 'lg'`
2. `CUIT: {clinica.cuit}` — `fixed_text`
3. `{clinica.direccion}` — `fixed_text`
4. `{clinica.email} | Tel: {clinica.telefono}` — `fixed_text`

Key observation: the **clinic name is the FIRST and most prominent field** in the column, with large/bold styling. The logo logically belongs **above** it, as a visual header to the entire clinic block.

#### 2.3 Variable Catalog (`app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php`)

`{clinica.logo}` is registered at **line 47**:
```php
new SystemVariable('clinica', 'logo', 'Logo', 'Logo o imagen institucional'),
```

The `clinica` category has **10 variables**: nombre, direccion, telefono, email, ciudad, provincia, codigo_postal, web, cuit, logo.

#### 2.4 Logo Infrastructure

| Component | Status | File |
|-----------|--------|------|
| DB column | ✅ `logo` (nullable string) in `clinics` | `database/migrations/2026_07_11_100444_add_logo_to_clinics.php` |
| Upload endpoint | ✅ `POST /admin/clinic/logo` (auth + permission) | `app/Http/Actions/Admin/Clinic/UploadClinicLogoAction.php` |
| Public serving | ✅ `GET /logos/{filename}` (no auth) | `app/Http/Actions/ShowLogoAction.php` |
| Storage | ✅ `Storage::disk('public')->storeAs('logos', ...)` | `app/Repositories/Clinic/ClinicSaveRepository.php` |
| Model fillable | ✅ `logo` in `$fillable` | `app/Models/Clinic.php` (line 24) |
| URL transformation | ✅ `toArray()` converts filename → full URL | `app/Models/Clinic.php` (line 35) |
| Old file cleanup | ✅ Deletes old logo on replace | `app/Repositories/Clinic/ClinicSaveRepository.php` (line 29) |

#### 2.5 Template Consumption

- When a report is created (`PatientReportSaveRepository`), the template's `structure` is snapshotted into `patient_reports.template_structure_snapshot`
- The `{clinica.*}` placeholders are currently **NOT resolved** at init, PDF generation, or anywhere server-side (per the `system-variable-substitution` exploration)
- Both the PDF view (`resources/views/reports/pdf.blade.php`) and `ExtractReportDataCommand` read the raw placeholders
- Resolution is expected to be handled by a future `VariableResolverService` (out of scope for this change)

---

### 3. What Needs to Change

#### 3.1 Single file: `database/seeders/ReportTemplatesSeeder.php`

In each of the 3 template methods, add a new field **before** the existing `{clinica.nombre}` field, inside row 2, column 2a.

**Field to add** (pattern repeated 3×):

```php
[
    'id' => 'uuid-PREFIX-field-header-clinica-logo',
    'key' => 'PREFIX_header_clinica_logo',
    'type' => 'fixed_text',
    'label' => 'Logo de la clínica',
    'required' => false,
    'showLabel' => false,
    'text_content' => '{clinica.logo}',
],
```

Where `PREFIX` is:
- `hcg` for Historia Clínica General (line ~88, before `uuid-hcg-field-header-clinica-nombre`)
- `ia` for Informe de Alta (line ~735, before `uuid-ia-field-header-clinica-nombre`)
- `ci` for Consentimiento Informado (line ~1227, before `uuid-ci-field-header-clinica-nombre`)

Each placement happens inside the `fields` array of the first column (col-2a) in the second header row (row-2).

#### 3.2 Exact insertion point per template

**HCG template** — File lines 88–100. Insert the new logo field before line 88 (the `uuid-hcg-field-header-clinica-nombre` field).

**IA template** — File lines 735–747. Insert the new logo field before line 735 (the `uuid-ia-field-header-clinica-nombre` field).

**CI template** — File lines 1227–1239. Insert the new logo field before line 1227 (the `uuid-ci-field-header-clinica-nombre` field).

#### 3.3 Files NOT touched

| File | Why NOT |
|------|---------|
| `app/Models/ReportTemplate.php` | No schema change — `structure` is `array` cast, logo is just a field inside it |
| `app/Commands/Admin/SystemVariable/GetSystemVariablesCommand.php` | `{clinica.logo}` already registered (line 47) |
| `app/Models/Clinic.php` | No change — logo field already exists and serializes to URL |
| `database/factories/ReportTemplateFactory.php` | Factory doesn't need to know about logo — it generates generic structures |
| Any migration | No DB schema change |
| `routes/api.php` | Logo endpoint already exists |

---

### 4. How the Logo Renders

The `{clinica.logo}` variable resolves to the **full URL** from the Clinic model's `toArray()` method (e.g., `http://localhost/logos/clinic-logo-hash.png`). This URL is served by `ShowLogoAction` which responds with the binary file.

The frontend (report viewer / PDF generator) is responsible for interpreting the variable value — whether to render it as an `<img>` tag, base64-embed it, or display the URL text. From the seeder perspective, it's just a `fixed_text` field like any other variable.

#### 4.1 Design consideration: `type` field

Currently `{clinica.nombre}` uses `type: 'fixed_text'`. The logo could potentially use a dedicated `image` type if one were defined in the field type system. However:

- The `fixed_text` type is **already established** and works identically for all variables
- No `image` field type exists in the current type catalog (see docs: text, textarea, number, date, select, multi_select, radio, checkbox, fixed_text, dynamic_table, horizontal_separator)
- The consuming system (frontend/PDF) can detect the `{clinica.logo}` variable and render it as an image based on the variable key, regardless of the field type
- Using `fixed_text` keeps the change minimal and avoids adding unnecessary complexity

**Recommendation**: Use `fixed_text` as the field type, consistent with how `{clinica.nombre}` works.

---

### 5. Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Existing stored templates won't get the logo field | **High** — seeders only run once | **Low** — only affects new installs; existing instances need a separate migration/seeder update | Document that existing DB clones need a manual re-seed or a targeted migration |
| Logo renders as plain text URL instead of image | **Medium** — depends on frontend interpretation | **Low** — the field is informational; frontend already handles `fixed_text` rendering | Ensure the frontend template builder recognizes `{clinica.logo}` for image rendering |
| `{clinica.logo}` resolves to empty string when no logo uploaded | **Medium** | **Low** — the `text_content` is `'{clinica.logo}'` which resolves to `null` → empty string, producing no visible output | Acceptable behavior: no logo = no rendering; clinic name still shows |
| Field position breaks existing UUID-based field resolution | **None** — UUIDs are unique per field | N/A | Standard UUID generation via `replaceUuids()` handles this |

---

### 6. Recommendation

**Proceed** — this is a **trivial, zero-risk change**. The infrastructure is fully in place:
1. `{clinica.logo}` is registered in the system variable catalog
2. The `logo` column exists in the database with file upload/serving endpoints
3. The template structure is flexible JSON with `array` casting
4. All 3 templates share an identical header pattern where the logo field drops in naturally

The only decision to make is whether to use `fixed_text` (consistent with `{clinica.nombre}`) or introduce a new field type. I recommend `fixed_text` — it keeps the change minimal and leaves the image-rendering logic to the consumer (frontend/PDF engine), which can do a better job with layout, sizing, and fallback than a hardcoded JSON structure.

### 7. Ready for Proposal

**Yes**. The change is well-understood, scoped, and has no blocking dependencies. The proposal should specify:
- 3 insertion points in the seeder
- Field type: `fixed_text`
- Variable: `{clinica.logo}`
- Placement: first field in the clinic info column, above the clinic name
