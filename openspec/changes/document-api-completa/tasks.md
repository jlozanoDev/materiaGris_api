# Tasks: Documentar API Completa

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~1,087 additions/modifications + ~390 file deletions = 1,477 total |
| 400-line budget risk | **High** — 1,087 meaningful review lines across 18 files |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (P0 tech core) → PR 2 (P0 func+indices) → PR 3 (P1 cross-cutting) → PR 4 (P2+P3 merge+polish) |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Reports technical docs + endpoints guide (modulo-informes.md, modulo-plantillas.md, guia-endpoints-api.md, tecnica/INDICE.md) | PR 1 | ~425 lines. Base: main |
| 2 | Functional docs + flow + indices (informes.md, plantillas-informes.md, gestion-informes.md, funcional/INDICE.md, docs/INDICE.md) | PR 2 | ~315 lines. Base: PR 1 branch |
| 3 | Cross-cutting docs (arquitectura.md, estructura-base-datos.md, modelo-permisos-roles.md, permisos.md) | PR 3 | ~195 lines. Base: PR 2 branch |
| 4 | Transcribe merge + polish (modulo-dictado-autocompletado rewrite, delete 2 old files, glossary, cross-links) | PR 4 | ~152 meaningful + 390 deletions. Base: PR 3 branch |

## Phase 1: P0 — Endpoints Guide + Reports/Templates Core Docs

- [x] 1.1 **CREATE** `docs/tecnica/modules/reports/modulo-informes.md` — Technical doc for 7 Reports CRUD endpoints + transcribe + extract-data. Sections: Routes table, Actions (7), Requests, Commands (ListReports..CloseReport), Repositories, PatientReport model, error codes, data flow diagram, development status. ~180 lines.
  - **Verification**: All 7 endpoints listed with correct Action class, permission slug, and middleware. `PatientReport` model attributes match migration.
  - **Deps**: Read `app/Http/Actions/Reports/*`, `app/Commands/Reports/*`, `app/Repositories/Report/*`, `app/Models/PatientReport.php`.

- [x] 1.2 **CREATE** `docs/tecnica/modules/reports/modulo-plantillas.md` — Technical doc for 5 admin CRUD + 1 public endpoint. Sections: Routes (admin + public), Actions (ListReportTemplates..DeleteReportTemplates + GetActiveTemplates), Requests, Commands, Repositories, ReportTemplate model with JSON structure (sections→rows→columns), variable placeholders, error codes, data flow. ~150 lines.
  - **Verification**: 6 endpoints documented. JSON structure (`sections/rows/columns`) explained with field types. `GetActiveTemplatesAction` scoping logic described.
  - **Deps**: Read `app/Http/Actions/Admin/ReportTemplate/*`, `app/Commands/Admin/ReportTemplate/*`, `app/Models/ReportTemplate.php`.

- [x] 1.3 **MODIFY** `docs/tecnica/guia-endpoints-api.md` — Add 15 missing endpoints, restructure Reports section. Actions: add Reports CRUD table (7 rows), Admin Templates subsection (5 rows), `GET /patients/{id}` in Patients table, `GET /templates/active` section, `POST /reports/{id}/transcribe` in Reports. Move existing `POST /reports/{id}/extract-data` into new Reports table. Update summary table: 23→38, Admin 12→17, Patients 3→4, Reports 1→9, Templates new=1. ~80 lines.
  - **Verification**: Count 38 table rows. Summary totals match: Health=1, Auth=6, Admin=17, Patients=4, Reports=9, Templates=1. All have method, URI, middleware, permiso, Action.

- [x] 1.4 **CREATE** `docs/funcional/modulos/informes.md` — Functional doc for Reports CRUD. Sections: business purpose, actors (medical professionals), CRUD functionalities table (7 endpoints), acceptance criteria, business rules (report creation with patient+template, draft saving, signing workflow, closing workflow, PDF download), data structure examples (request/response), dependencies (permissions), development status. ~110 lines.
  - **Verification**: All 7 operations covered. Sign/close workflow described. Data examples include `content` JSON structure.
  - **Deps**: Task 1.1 (needs technical endpoint details).

- [x] 1.5 **CREATE** `docs/funcional/modulos/plantillas-informes.md` — Functional doc for Report Templates. Sections: business purpose, actors (admin only), template lifecycle table (create→edit→activate/deactivate→delete), acceptance criteria, business rules (section/field hierarchy, activation/deactivation, restriction against deleting templates in use), variable placeholder syntax (`{{patient.name}}`, etc.), data structure examples, development status. ~90 lines.
  - **Verification**: Lifecycle covers all states. Variable syntax documented with examples. Org-scoping mentioned.
  - **Deps**: Task 1.2 (needs technical endpoint details).

- [x] 1.6 **CREATE** `docs/funcional/flujos/gestion-informes.md` — Flow doc for Reports operations. Sections: init report flow, save draft flow, sign flow, close flow, download PDF flow, error flows (unauthorized, invalid report, concurrency). Each step: API call, required permissions, request/response format. ~90 lines.
  - **Verification**: Complete end-to-end sequence: init→save→sign→close→download. Error scenarios covered: 401, 403, 404, 409.
  - **Deps**: Tasks 1.1, 1.4 (needs endpoint list and business rules).

- [x] 1.7 **MODIFY** `docs/tecnica/INDICE.md` — Add 2 entries to Modules table: "Reports — CRUD" → `modules/reports/modulo-informes.md`, "Admin — Plantillas" → `modules/reports/modulo-plantillas.md`. Add 2 rows to cross-reference table linking Reports and Templates technical docs to their functional counterparts. ~15 lines.
  - **Verification**: Modules table shows 9 entries (was 7). Cross-ref table shows 2 new rows with valid relative paths.
  - **Deps**: Tasks 1.1, 1.2 (needs exact filenames and paths).

- [x] 1.8 **MODIFY** `docs/funcional/INDICE.md` — Add "Informes" and "Plantillas de Informes" to modules table. Add "Gestión de Informes" to flows table. Update coverage table: add 2 rows (✅ Documentado, ✅ Completo). ~20 lines.
  - **Verification**: Modules table: 9 entries (was 7). Flows: 5 entries (was 4). Coverage: 12 rows (was 10).
  - **Deps**: Tasks 1.4, 1.5, 1.6 (needs exact filenames and paths).

- [x] 1.9 **MODIFY** `docs/INDICE.md` — Verify all links remain valid. No structural change needed. ~5 lines (link fixes if any stale paths found).
  - **Verification**: All links to `docs/tecnica/INDICE.md` and `docs/funcional/INDICE.md` resolve correctly.
  - **Deps**: Tasks 1.7, 1.8 (sub-indices must be updated first).

## Phase 2: P1 — Cross-cutting Docs (Architecture, DB, Permissions)

- [x] 2.1 **MODIFY** `docs/tecnica/arquitectura.md` — Add "Reports" and "Admin — Templates" rows to layer-map table. Update directory tree: add `Commands/Reports/`, `Http/Actions/Reports/`, `Http/Actions/Admin/ReportTemplate/`, `Repositories/Report/`. Add `SpeakerClassifierService` to Services list with description. Remove stale `backend/` prefix from any paths. ~55 lines.
  - **Verification**: Layer-map table has 8 rows (was 6). Reports row shows Actions=7, Commands=7. `SpeakerClassifierService` listed. Zero `backend/` prefixes.
  - **Deps**: Task 1.1 (needs module path conventions).

- [x] 2.2 **MODIFY** `docs/tecnica/estructura-base-datos.md` — Add 3 new tables: `patient_reports` (columns, FKs, cascade rules, PatientReport model ref), `report_templates` (columns, JSON structure explanation, ReportTemplate model ref), `llm_interactions` (columns, FK to patient_reports CASCADE, LlmInteraction model ref). Renumber tables 10-19 → 13-22. Update header "19 tablas" → "22 tablas". ~75 lines.
  - **Verification**: 22 tables listed. `patient_reports` shows FK→patients, FK→report_templates. `structure` JSON shape explained. Renumbering consistent across all table references.
  - **Deps**: Read migrations: `*_create_patient_reports_table`, `*_create_report_templates_table`, `*_create_llm_interactions_table`.

- [x] 2.3 **MODIFY** `docs/tecnica/modelo-permisos-roles.md` — Add 6 `report.*` + 4 `admin.reporttemplate.*` + `admin.permission.view` to seed data table. Update `/auth/me` response example: 7→22+ permission keys. ~40 lines.
  - **Verification**: Permissions table shows 22+ entries. `report.view|create|edit|sign|close|download-pdf` present. `admin.reporttemplate.view|create|update|delete` present. Example response has all new keys.
  - **Deps**: Read `database/seeders/PermissionSeeder.php` to verify exact slugs and categories.

- [x] 2.4 **MODIFY** `docs/funcional/modulos/administracion/permisos.md` — Add 6 `report.*` + 4 `admin.reporttemplate.*` + `admin.permission.view` to functional permissions catalog table with slug, action description, and category. ~25 lines.
  - **Verification**: Permissions table shows 22+ entries (was 12). New permissions categorized under "informes" or respective admin groups.
  - **Deps**: Task 2.3 (must match technical catalog).

## Phase 3: P2 — Consolidate Transcribe Duplicates + SpeakerClassifier

- [x] 3.1 **MODIFY** `docs/tecnica/modules/reports/modulo-dictado-autocompletado.md` — Merge content from `backend-api-transcribe.md` (222 lines, backend-oriented) and `backend-transcribe.md` (168 lines, frontend-oriented) into this file. Preserve: endpoint spec, request/response contracts, error codes, diarization rules, audio MIME types, STT config, implementation architecture, frontend integration notes. Add new "Speaker Classifier" subsection: purpose, input (segments array), output (segments with `role` field), integration point. Update cross-refs to point to new location. ~120 lines (file grows from 117→~220).
  - **Verification**: Diff old files vs merged file — no technical content lost. Speaker classifier subsection present. File in correct location (`modules/reports/`).
  - **Deps**: Read both source files in full. Read `app/Services/SpeakerClassifierService.php`.

- [x] 3.2 **DELETE** `docs/tecnica/backend-transcribe.md` — Remove after merge verified. 168-line deletion.
  - **Verification**: File no longer exists. No references to it remain in other docs.
  - **Deps**: Task 3.1 (merge must be verified complete first).

- [x] 3.3 **DELETE** `docs/tecnica/backend-api-transcribe.md` — Remove after merge verified. 222-line deletion.
  - **Verification**: File no longer exists. No references to it remain in other docs.
  - **Deps**: Task 3.1 (merge must be verified complete first).

- [x] 3.4 **MODIFY** cross-references — Update `docs/tecnica/INDICE.md` and any other files referencing old transcribe paths to point to `modules/reports/modulo-dictado-autocompletado.md`. ~10 lines.
  - **Verification**: `grep -r "backend-transcribe\|backend-api-transcribe" docs/` returns zero results.
  - **Deps**: Tasks 3.2, 3.3 (old files must be gone before verifying no stale refs).

## Phase 4: P3 — Polish

- [x] 4.1 **MODIFY** `docs/funcional/glosario-terminos.md` — Add 5-6 new terms: Informe, Plantilla de Informe, Firma de Informe, Cierre de Informe, Campo de Plantilla, Variable de Sistema. ~12 lines.
  - **Verification**: Glossary has 22+ entries (was 17). Each new term has a clear, business-oriented definition.
  - **Deps**: Tasks 1.4, 1.5 (functional docs provide the definitions).

- [x] 4.2 **VERIFY** cross-links across all documentation files — Run manual grep for broken `.md` links. Fix any found. Verify counts: 38 endpoints in guia, 22 tables in BD schema, 22+ permissions, 9 tech modules, 9 func modules. ~10 lines of fixes.
  - **Verification**: Zero broken relative links. All counts match proposal success criteria.
  - **Deps**: ALL previous tasks complete.

## Dependency Graph

```
1.1 (modulo-informes) ──┬──▶ 1.3 (guia-endpoints) ──▶ 1.4 (informes func)
                        │                            ──▶ 1.5 (plantillas func)
1.2 (modulo-plantillas)─┘                                    
                        │                            
                        ├──▶ 1.7 (tecnica/INDICE) ──┐
                        │                            ├──▶ 1.9 (docs/INDICE)
1.4, 1.5, 1.6 ──────────┼──▶ 1.8 (funcional/INDICE)─┘
                        │
1.6 (gestion-informes) ─┘

1.1 ──▶ 2.1 (arquitectura)
2.2 (BD schema)    — independent (read migrations only)
2.3 (permisos tec) — independent (read seeder only)
2.3 ──▶ 2.4 (permisos func)

3.1 (merge transcribe) ──▶ 3.2, 3.3 (delete old files) ──▶ 3.4 (cross-refs)

1.4, 1.5 ──▶ 4.1 (glossary)
ALL ──────▶ 4.2 (cross-link verify)
```

## Verification Checklist (per-spec scenario mapping)

| Spec Scenario | Task(s) |
|---------------|---------|
| Reports table with 7 rows in endpoints guide | 1.3 |
| Admin Templates subsection with 5 rows | 1.3 |
| GET /patients/{id} added to Patients table | 1.3 |
| Summary table: 38 total, Admin=17, Reports=9, Templates=1 | 1.3 |
| modulo-informes.md covers all 7 endpoints with Actions+Commands+Models | 1.1 |
| modulo-plantillas.md covers 6 endpoints + JSON structure explained | 1.2 |
| informes.md (funcional) describes sign/close workflow | 1.4 |
| plantillas-informes.md (funcional) covers lifecycle + variable placeholders | 1.5 |
| gestion-informes.md flow doc: init→save→sign→close→download | 1.6 |
| Glossary: 4+ new terms for Reports | 4.1 |
| Glossary: 3+ new terms for Templates | 4.1 |
| Layer map: Reports and Templates rows added | 2.1 |
| Directory tree: Reports subdirs + no backend/ prefix | 2.1 |
| SpeakerClassifierService in services list | 2.1 |
| patient_reports table documented with all columns + FKs | 2.2 |
| report_templates table documented with JSON shape explained | 2.2 |
| llm_interactions table documented | 2.2 |
| Summary: "22 tablas" + renumbering | 2.2 |
| 6 report.* permissions in seed table | 2.3 |
| 4 admin.reporttemplate.* permissions in seed table | 2.3 |
| admin.permission.view documented | 2.3 |
| /auth/me example shows 22+ permission keys | 2.3 |
| Functional permissions catalog: 22+ entries | 2.4 |
| Single transcribe file in modules/reports/ | 3.1 |
| Both old standalone files deleted | 3.2, 3.3 |
| No content lost from deleted files | 3.1 |
| No broken cross-refs to old transcribe paths | 3.4 |
| Tech index: Reports and Templates module entries | 1.7 |
| Func index: Informes, Plantillas modules + flow + coverage | 1.8 |
| Master index: links valid | 1.9 |
