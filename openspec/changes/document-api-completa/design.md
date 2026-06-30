# Design: Documentar API Completa

## Technical Approach

Documentation-only change. Follow existing project doc conventions (`prompt-ia-documentacion-tecnica.md`, `prompt-ia-documentacion-funcional.md`) as structure templates. Four phases: P0 (endpoints guide + Reports/Templates new docs + INDICE), P1 (arquitectura/BD/permisos updates), P2 (transcribe merge + SpeakerClassifierService), P3 (polish/glossary/cross-links). No code changes — read codebase for accurate content.

## Architecture Decisions

| # | Decision | Option A | Option B | Choice | Rationale |
|---|----------|----------|----------|--------|-----------|
| D1 | Reports module dir placement | `docs/tecnica/modules/reports/` alongside existing `modulo-dictado-autocompletado.md` | New top-level `modules/reports-crud/` | **A** | Reports is already a `routes` namespace; co-locate all reports docs in same dir |
| D2 | Transcribe merge strategy | Use `backend-api-transcribe.md` (222 lines, backend-structured) as base, integrate missing sections from `backend-transcribe.md` (168 lines, frontend-oriented) | Start from scratch | **A (merge into existing)** | `modulo-dictado-autocompletado.md` already exists (117 lines) — merge into it, not a new file. Both old files deleted |
| D3 | Endpoints guide table format | Preserve current: `Method | URI | Middleware | Permiso | Action | Descripción` | Reformat all | **A** | Existing format is clear; just add 15 missing rows and update summary |
| D4 | Doc generation template | Use project prompts (`prompt-ia-documentacion-{tecnica,funcional}.md`) | Ad-hoc structure | **A** | Project convention; ensures consistency with existing 7 module docs |
| D5 | Functional flow file | Single `flujos/gestion-informes.md` covering all reports operations | Separate flow per operation | **A (single file)** | Follows existing pattern (4 flow files, each covering a module's operations) |

## Data Flow

```
routes/api.php ──read──▶ guia-endpoints-api.md updated (38 rows)
     │
app/Http/Actions/Reports/* ──read──▶ modulo-informes.md + informes.md + flujo
app/Commands/Reports/* ──read──▶
app/Repositories/Report/* ──read──▶
app/Models/PatientReport.php ──read──▶

app/Http/Actions/Admin/ReportTemplate/* ──read──▶ modulo-plantillas.md + plantillas-informes.md
app/Commands/Admin/ReportTemplate/* ──read──▶
app/Models/ReportTemplate.php ──read──▶

app/Services/SpeakerClassifierService.php ──read──▶ modulo-dictado-autocompletado.md (updated)

database/migrations/*_patient_reports.php ──read──▶ estructura-base-datos.md (+3 tables)
database/migrations/*_report_templates.php ──read──▶
database/migrations/*_llm_interactions.php ──read──▶

database/seeders/PermissionSeeder.php ──read──▶ modelo-permisos-roles.md (12→22+)

backend-api-transcribe.md ──merge──▶ modulo-dictado-autocompletado.md (updated, unified)
backend-transcribe.md ──merge──▶ (then delete both standalone files)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `docs/tecnica/guia-endpoints-api.md` | Modify | Add 15 missing endpoints: 7 Reports CRUD, 5 Admin Templates, 1 GET /patients/{id}, 1 GET /templates/active, 1 POST /reports/{id}/transcribe. Update summary 23→38 |
| `docs/tecnica/modules/reports/modulo-informes.md` | **Create** | 7 CRUD endpoints + transcribe + extract-data. Actions, Commands (ListReportsCommand..CloseReportCommand), Repositories, PatientReport model |
| `docs/tecnica/modules/reports/modulo-plantillas.md` | **Create** | 5 admin CRUD + GET /templates/active. Sections→rows→columns JSON structure. Variable placeholders |
| `docs/tecnica/modules/reports/modulo-dictado-autocompletado.md` | Modify | Merge transcribe content from 2 old files, add SpeakerClassifierService section, update cross-refs |
| `docs/funcional/modulos/informes.md` | **Create** | Business purpose, actors, CRUD rules, sign/close workflow, PDF download, data structures |
| `docs/funcional/modulos/plantillas-informes.md` | **Create** | Template lifecycle, activation/deactivation, variable autocompletion, admin actors |
| `docs/funcional/flujos/gestion-informes.md` | **Create** | Main flow (create→edit→sign→close→download) + error flows, API calls, permissions per step |
| `docs/tecnica/arquitectura.md` | Modify | Add Reports/Templates to layer map table, update directory tree (Reports/ actions, Commands, Repositories), remove stale `backend/` prefix, add SpeakerClassifierService |
| `docs/tecnica/estructura-base-datos.md` | Modify | Add tables 10-12: patient_reports, report_templates, llm_interactions. Renumber 10-19→13-22. Update summary 19→22 |
| `docs/tecnica/modelo-permisos-roles.md` | Modify | Add 6 `report.*` + 4 `admin.reporttemplate.*` permissions to seed table. Update `/auth/me` example with 22+ keys |
| `docs/funcional/modulos/administracion/permisos.md` | Modify | Add report/template permissions to functional catalog table |
| `docs/funcional/glosario-terminos.md` | Modify | Add terms: Informe, Plantilla de Informe, Firma de Informe, Cierre de Informe, Campo de Plantilla, Variable de Sistema |
| `docs/tecnica/INDICE.md` | Modify | Add modulo-informes.md and modulo-plantillas.md to modules table. Add cross-ref rows to new functional docs |
| `docs/funcional/INDICE.md` | Modify | Add Informes and Plantillas to modules table. Add flujos/gestion-informes.md. Update coverage table (2 new rows: ✅ Documentado, ✅ Completo) |
| `docs/INDICE.md` | Modify | Verify links remain valid (no structural change needed per spec) |
| `docs/tecnica/backend-api-transcribe.md` | Delete | Merged into modulo-dictado-autocompletado.md |
| `docs/tecnica/backend-transcribe.md` | Delete | Merged into modulo-dictado-autocompletado.md |

## Codebase Sources per Endpoint

| Endpoint group | Must read Actions | Must read Commands | Must read Models | Must read Migrations/Seeders |
|---------------|-------------------|-------------------|------------------|---------------------------|
| Reports CRUD (7) | ListReports, InitReport, GetReport, SaveDraftReport, SignReport, CloseReport, DownloadPdfReport | Same-name Commands under `app/Commands/Reports/` | PatientReport | create_patient_reports_table, llm_interactions |
| Reports extract/transcribe (2) | ExtractReportData, TranscribeReport | Same-name Commands | PatientReport, ReportTemplate | (already read) |
| Admin Templates (5) | ListReportTemplates, CreateReportTemplate, GetReportTemplate, UpdateReportTemplate, DeleteReportTemplate | Same-name Commands under `app/Commands/Admin/ReportTemplate/` | ReportTemplate | create_report_templates_table, ReportTemplateSeeder |
| Active Templates (1) | GetActiveTemplates | (reads via Repository) | ReportTemplate | (already read) |
| Patients/{id} (1) | GetPatient | (reads via Repository) | Patient | (already documented) |
| Architecture cross-cutting | — | — | All models | All migrations |
| Permissions | — | PermissionService | Permission, Role | PermissionSeeder, RoleSeeder |
| SpeakerClassifier | — | — | — | (service-only, not DB) |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Link validation | All cross-references between 18 files | Manual grep for broken `.md` links after each phase |
| Endpoint count | 38 rows in guia-endpoints-api.md | Count table rows vs routes/api.php |
| Permission count | 22+ permissions in modelo-permisos-roles.md | Count slugs vs PermissionSeeder |
| Table count | 22 tables in estructura-base-datos.md | Count vs migrations |
| Merge completeness | No content loss from deleted transcribe files | Diff old files vs new modulo-dictado-autocompletado.md |

## Open Questions

- [ ] D5: Confirm `flujos/gestion-informes.md` naming matches existing pattern vs. `flujo-informes.md`? (resolved: follow existing pattern — `gestion-pacientes.md`, `dictado-autocompletado.md`)
- [ ] D2: Confirm merge target is `modulo-dictado-autocompletado.md` (existing, 117 lines) not a new file? (resolved: yes, per transcribe spec requiring single unified file in `modules/reports/`)
- [ ] Confirm `admin.permission.view` exists in seeder but absent from docs? (needs verification during P1)
