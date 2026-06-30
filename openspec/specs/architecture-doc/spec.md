# architecture-doc Specification

## Purpose

Update `docs/tecnica/arquitectura.md` to remove outdated `backend/` path references, add Reports/Templates to layer maps, and add missing Commands/Services/Repositories to component tables.

## Requirements

### Requirement: Reports Layer Map Added

The system SHALL add Reports and Admin-Templates rows to the "Mapa Módulo-vs-Capa" table.

#### Scenario: Reports row added

- GIVEN the table has 6 rows (Health, Auth, Patients, Admin-Users, Admin-Roles, Admin-Permissions)
- WHEN Reports is added
- THEN a "Reports" row SHALL show: Actions=7, Commands=7, Repositories=2, Models=1, Services=LlmExtractorService+SpeakerClassifierService
- AND an "Admin — Templates" row SHALL show: Actions=5, Commands=5, Repositories=1, Models=1, Services=0

### Requirement: Directory Structure Updated

The system SHALL update `app/` directory structure to include Reports subdirectories under `Commands/`, `Http/Actions/`, and `Repositories/`.

#### Scenario: Reports directories documented

- GIVEN the directory tree lists `Commands/Admin/`, `Commands/Auth/`, `Commands/Health/`
- WHEN the tree is updated
- THEN `Commands/Reports/` SHALL appear with all 10 report commands
- AND `Http/Actions/Reports/` SHALL appear with 10 report actions
- AND `Http/Actions/Admin/ReportTemplate/` SHALL appear

### Requirement: Backend Prefix References Removed

The system SHALL remove or correct all `backend/` prefix references that do not exist in the current repository structure.

#### Scenario: No stale paths

- GIVEN the current architecture doc may reference `backend/app/` paths
- WHEN the doc is reviewed and updated
- THEN no file path SHALL contain `backend/` prefix
- AND all paths SHALL match the actual monorepo root structure

### Requirement: SpeakerClassifierService Added

The system SHALL add `SpeakerClassifierService` to the Services listing.

#### Scenario: Speaker classifier in services

- GIVEN `SpeakerClassifierService.php` exists in `app/Services/`
- WHEN services list is updated
- THEN `SpeakerClassifierService` SHALL appear with description: "Clasifica segmentos de audio por rol (médico/paciente)"
