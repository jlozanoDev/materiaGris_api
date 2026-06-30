# Proposal: Documentar API Completa

## Intent

38 endpoints están implementados pero solo 23 documentados (60%). 15 endpoints — Reports CRUD (7), Report Templates (5), `patients/{id}`, `templates/active`, transcribe — no aparecen en la guía ni tienen docs de módulo. Las guías transversales (arquitectura, BD, permisos) están desactualizadas. Dos specs de transcribe duplicadas. La documentación funcional no cubre Reports ni Templates. Esto frena onboarding, revisión de PRs y handoff entre equipos.

## Scope

### In Scope
- Actualizar `guia-endpoints-api.md`: 23 → 38 endpoints con método, URI, middleware, permiso, Action
- Crear docs técnicas para módulos Reports CRUD y Report Templates en `docs/tecnica/modules/`
- Crear docs funcionales, flujos y glosario para Reports y Templates en `docs/funcional/`
- Consolidar `backend-api-transcribe.md` + `backend-transcribe.md` en un solo archivo
- Documentar `SpeakerClassifierService` en el módulo de transcribe
- Actualizar `arquitectura.md` (paths, capas), `estructura-base-datos.md` (+3 tablas: `patient_reports`, `report_templates`, `llm_interactions`), `modelo-permisos-roles.md` (12→22+ permisos)
- Actualizar INDICE.md (maestro, técnico, funcional) con nuevas entradas
- Cross-linking entre archivos técnicos y funcionales

### Out of Scope
- Cambios de código, refactors, tests, nuevos endpoints
- Documentación de endpoints no implementados (Consultas)
- Traducción de docs existentes (permanecen en español)
- Documentación del frontend

## Capabilities

### New Capabilities
- `reports-crud`: documentación técnica y funcional de CRUD de informes (7 endpoints)
- `report-templates`: documentación técnica y funcional de plantillas (5+1 endpoints)

### Modified Capabilities
- `endpoints-guide`: expandir de 23 a 38 endpoints documentados
- `architecture-doc`: actualizar paths, capas y flujo hexagonal
- `database-schema`: añadir 3 tablas faltantes
- `permissions-catalog`: expandir de 12 a 22+ permisos
- `transcribe-specs`: consolidar 2 archivos duplicados en 1

## Approach

Revisar cada endpoint contra su Action, leer código fuente, escribir documentación por módulo siguiendo los prompts IA del proyecto (`prompt-ia-documentacion-tecnica.md`, `prompt-ia-documentacion-funcional.md`). Cuatro fases incrementales.

## Affected Files

| Área | Impacto | Archivos |
|------|---------|----------|
| Guía endpoints | Modified | `docs/tecnica/guia-endpoints-api.md` |
| Módulo Reports | New | `docs/tecnica/modules/reports/modulo-informes.md` |
| Módulo Templates | New | `docs/tecnica/modules/reports/modulo-plantillas.md` (o `modules/admin/report-templates-crud.md`) |
| Funcional Reports | New | `docs/funcional/modulos/informes.md` |
| Funcional Templates | New | `docs/funcional/modulos/plantillas-informes.md` |
| Flujos | New/Modified | `docs/funcional/flujos/gestion-informes.md`, `flujos/dictado-autocompletado.md` |
| Glosario | Modified | `docs/funcional/glosario-terminos.md` |
| Transcribe specs | Merged | `backend-api-transcribe.md` + `backend-transcribe.md` → unificado |
| Arquitectura | Modified | `docs/tecnica/arquitectura.md` |
| BD Schema | Modified | `docs/tecnica/estructura-base-datos.md` |
| Permisos | Modified | `docs/tecnica/modelo-permisos-roles.md` |
| Índices | Modified | `docs/INDICE.md`, `docs/tecnica/INDICE.md`, `docs/funcional/INDICE.md` |

## Phased Execution

### P0 — Endpoints Guide + Reports/Templates Core (14 endpoints)
- `guia-endpoints-api.md`: añadir 15 endpoints faltantes (total 38)
- `docs/tecnica/modules/reports/modulo-informes.md` (nuevo: 7 endpoints CRUD + sign + close + pdf)
- `docs/tecnica/modules/reports/modulo-plantillas.md` (nuevo: 5 admin + 1 templates/active)
- `docs/funcional/modulos/informes.md` (nuevo)
- `docs/funcional/modulos/plantillas-informes.md` (nuevo)
- `docs/funcional/flujos/gestion-informes.md` (nuevo)
- Actualizar todos los INDICE

### P1 — Cross-cutting Docs (arquitectura, BD, permisos)
- `arquitectura.md`: actualizar paths, layer map
- `estructura-base-datos.md`: añadir `patient_reports`, `report_templates`, `llm_interactions`
- `modelo-permisos-roles.md`: 12→22+ permisos (report.*, admin.reporttemplate.*, etc.)

### P2 — Consolidate Duplicates + New Service
- Merge `backend-api-transcribe.md` + `backend-transcribe.md` → 1 archivo unificado
- Documentar `SpeakerClassifierService` en módulo transcribe
- `docs/tecnica/modules/reports/modulo-dictado-autocompletado.md`: actualizar con SpeakerClassifier

### P3 — Polish
- Corregir typos, verificar cross-links, actualizar glosario con términos Reports/Templates
- Actualizar estado de cobertura en `docs/funcional/INDICE.md`

## Risks

| Riesgo | Probabilidad | Mitigación |
|--------|-------------|------------|
| Endpoints con lógica no documentada en Actions | Media | Leer Actions y Commands antes de documentar |
| Permisos nuevos no mapeados en BD | Baja | Verificar `permissions` table y seeders |
| Cross-links rotos por reestructuración | Baja | Verificar todos los links al final de cada fase |

## Success Criteria

- [ ] `guia-endpoints-api.md` lista los 38 endpoints con método, URI, middleware, permiso, Action
- [ ] Cada endpoint tiene doc técnica en su módulo (`docs/tecnica/modules/`)
- [ ] Cada módulo tiene doc funcional, flujo y entrada en glosario
- [ ] `estructura-base-datos.md` cubre las 22 tablas
- [ ] `modelo-permisos-roles.md` cubre 22+ permisos
- [ ] Transcribe: 1 archivo unificado, NO 2
- [ ] `SpeakerClassifierService` documentado
- [ ] Los 3 INDICE están actualizados con todas las entradas nuevas
- [ ] 0 enlaces rotos entre archivos de documentación
