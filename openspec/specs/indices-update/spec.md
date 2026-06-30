# indices-update Specification

## Purpose

Update all three index files (`docs/INDICE.md`, `docs/tecnica/INDICE.md`, `docs/funcional/INDICE.md`) with entries for new Reports and Templates modules.

## Requirements

### Requirement: Master Index Updated

The system SHALL update `docs/INDICE.md` with no structural changes (the master index is already general) but verify its links remain valid.

#### Scenario: Master index links validated

- GIVEN new modules are added to sub-indices
- WHEN master index is checked
- THEN links to `docs/tecnica/INDICE.md` and `docs/funcional/INDICE.md` SHALL remain valid

### Requirement: Technical Index Updated

The system SHALL add `modulo-informes.md` and `modulo-plantillas.md` to the Modules table in `docs/tecnica/INDICE.md`.

#### Scenario: New module entries in tech index

- GIVEN the tech index Modules table has 7 existing entries
- WHEN updated
- THEN "Reports — CRUD" SHALL appear with file `modules/reports/modulo-informes.md`
- AND "Admin — Plantillas" SHALL appear with file `modules/reports/modulo-plantillas.md`
- AND the cross-reference table SHALL include rows linking to new functional docs

### Requirement: Functional Index Updated

The system SHALL update `docs/funcional/INDICE.md` adding Reports and Templates to módulos, a new flow entry, and updating the coverage table.

#### Scenario: New functional modules listed

- GIVEN the functional index modules table has 7 entries
- WHEN updated
- THEN "Informes" SHALL appear with link to `modulos/informes.md`
- AND "Plantillas de Informes" SHALL appear with link to `modulos/plantillas-informes.md`

#### Scenario: New flow entry added

- GIVEN the Flows table has 4 entries
- WHEN updated
- THEN "Gestión de Informes" SHALL appear with link to `flujos/gestion-informes.md`

#### Scenario: Coverage table updated

- GIVEN the coverage table shows 10 rows with various statuses
- WHEN updated
- THEN "Informes (CRUD)" SHALL appear with ✅ Documentado, ✅ Completo
- AND "Plantillas de Informes" SHALL appear with ✅ Documentado, ✅ Completo
