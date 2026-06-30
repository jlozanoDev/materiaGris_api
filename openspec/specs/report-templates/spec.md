# report-templates Specification

## Purpose

Document the Report Templates module (5+1 endpoints) across technical and functional documentation layers.

## Requirements

### Requirement: Technical Module Documentation

The system SHALL create `docs/tecnica/modules/reports/modulo-plantillas.md` covering 5 admin CRUD endpoints (`GET/POST/GET/PUT/DELETE /admin/report-templates`) + 1 public endpoint (`GET /templates/active`) with Actions, Commands, Repositories, Models, and error codes.

#### Scenario: Admin CRUD endpoints documented

- GIVEN 5 admin report-template routes in `routes/api.php`
- WHEN the technical doc is written
- THEN it SHALL document: `ListReportTemplatesAction`, `CreateReportTemplateAction`, `GetReportTemplateAction`, `UpdateReportTemplateAction`, `DeleteReportTemplateAction`
- AND each SHALL list required permission: `admin.reporttemplate.{view|create|update|delete}`

#### Scenario: Active templates endpoint documented

- GIVEN `GET /templates/active` with `require_permissions:report.create`
- WHEN the endpoint is documented
- THEN it SHALL describe the `GetActiveTemplatesAction`, filtering logic (active only, org-scoped), and response format

#### Scenario: Template structure documented

- GIVEN `ReportTemplate` model with fields: name, sections→rows→columns hierarchy, `is_active`, `organization_id`
- WHEN the model section is written
- THEN the nested structure (sections/rows/columns) SHALL be explained with field types (`text`, `textarea`, `number`, `date`, `select`, etc.)

### Requirement: Functional Module Documentation

The system SHALL create `docs/funcional/modulos/plantillas-informes.md` with business purpose, actors (admin), template lifecycle (create → edit → activate/deactivate → delete), and variable autocompletion integration.

#### Scenario: Template lifecycle business rules

- GIVEN an administrator managing report templates
- WHEN the functional doc describes the lifecycle
- THEN it SHALL cover: creation with section/field hierarchy, activation/deactivation, restriction against deleting templates in use
- AND variable placeholders (`{{patient.name}}`, `{{patient.age}}`) SHALL be documented

### Requirement: Glossary Update

The system SHALL modify `docs/funcional/glosario-terminos.md` adding terms: Plantilla de Informe, Campo de Plantilla, Variable de Sistema.

#### Scenario: Template glossary entries

- GIVEN the glossary lacks template-related terms
- WHEN templates are documented
- THEN at least 3 new entries SHALL be present
