# reports-crud Specification

## Purpose

Document the Reports CRUD module (7 endpoints) across technical, functional, and flow documentation layers.

## Requirements

### Requirement: Technical Module Documentation

The system SHALL create `docs/tecnica/modules/reports/modulo-informes.md` covering all 7 endpoints with Actions, Commands, Repositories, Models, middleware, data flow, error codes, and development status per the project's technical documentation prompt (`prompt-ia-documentacion-tecnica.md`).

#### Scenario: Module doc covers all endpoints

- GIVEN the route file `routes/api.php` defines 7 reports endpoints
- WHEN the technical module doc is written
- THEN it SHALL document: `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `POST /{id}/sign`, `POST /{id}/close`, `GET /{id}/pdf`
- AND each endpoint SHALL list: method, URI, middleware (`auth.jwt` + `require_permissions`), permission slug, Action class

#### Scenario: Model PatientReport is documented

- GIVEN the `PatientReport` model exists with fields: content, patient_id, template_id, status, signed_by, signed_at, closed_at
- WHEN the technical doc describes the data layer
- THEN the `PatientReport` model SHALL be listed with key attributes and relationships

### Requirement: Functional Module Documentation

The system SHALL create `docs/funcional/modulos/informes.md` with business purpose, actors, functionalities, acceptance criteria, business rules, data structure examples, and development status.

#### Scenario: Business rules for CRUD operations

- GIVEN the Reports module has CRUD operations
- WHEN the functional doc is written
- THEN it SHALL describe: report creation with patient+template selection, draft saving, signing workflow, closing workflow, PDF download

### Requirement: Flow Documentation

The system SHALL create `docs/funcional/flujos/gestion-informes.md` documenting the main flow (create → edit → sign → close → download) and error flows (unauthorized, invalid report, concurrency conflicts).

#### Scenario: Main flow sequence is documented

- GIVEN a medical professional creating an inform
- WHEN the flow doc describes the sequence
- THEN it SHALL cover: init report → save draft → sign → close → download PDF
- AND each step SHALL specify the API call, required permissions, and response format

### Requirement: Glossary Update

The system SHALL modify `docs/funcional/glosario-terminos.md` adding terms: Informe, Plantilla de Informe, Firma de Informe, Cierre de Informe.

#### Scenario: New glossary entries

- GIVEN the glossary has 21 entries
- WHEN reports terms are added
- THEN at least 4 new entries SHALL be present: "Informe", "Plantilla de Informe", "Firma de Informe", "Cierre de Informe"
