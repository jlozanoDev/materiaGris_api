# endpoints-guide Specification

## Purpose

Expand `docs/tecnica/guia-endpoints-api.md` from 23 to 38 endpoints, documenting all implemented routes.

## Requirements

### Requirement: Reports Endpoints Documented

The system SHALL add 7 Reports CRUD endpoints: `GET /reports`, `POST /reports`, `GET /reports/{id}`, `PUT /reports/{id}`, `POST /reports/{id}/sign`, `POST /reports/{id}/close`, `GET /reports/{id}/pdf`.

#### Scenario: Reports table added

- GIVEN the current guide has only `POST /reports/{id}/extract-data` in the Reports section
- WHEN the guide is updated
- THEN a Reports CRUD table SHALL appear with 7 rows: method, URI, middleware, permission, Action class for each endpoint
- AND existing `POST /reports/{id}/extract-data` and `POST /reports/{id}/transcribe` SHALL move into this table

### Requirement: Report Templates Endpoints Documented

The system SHALL add 5 admin templates endpoints: `GET/POST/GET/PUT/DELETE /admin/report-templates` + `GET /templates/active` (6 total).

#### Scenario: Templates in admin section

- GIVEN the admin section has Users, Roles, Permissions, System Variables tables
- WHEN templates endpoints are added
- THEN a new "Plantillas" subsection SHALL appear under Admin with 5 rows
- AND a new "Templates" section SHALL appear for `GET /templates/active`

### Requirement: Missing Patient Endpoint Documented

The system SHALL add `GET /patients/{id}` with `patient.view` permission and `GetPatientAction`.

#### Scenario: Single patient endpoint added

- GIVEN the Patients table lists only `find`, `POST`, `PUT /{id}`
- WHEN the guide is updated
- THEN `GET /patients/{id}` SHALL be added with its permission and Action

### Requirement: Logo Endpoints Documented

The system SHALL add 2 logo endpoints to `docs/tecnica/guia-endpoints-api.md`.

#### Scenario: Upload endpoint in admin section

- GIVEN the Admin/Clinic section lists `GET` and `PUT /admin/clinic`
- WHEN the guide is updated
- THEN `POST /admin/clinic/logo` SHALL appear with auth middleware, `admin.clinic.update` permission, and `UploadClinicLogoAction`
- AND `GET /admin/clinic` SHALL note the extended `logo` field in its response

#### Scenario: Public logo serving endpoint

- GIVEN the guide documents public routes
- WHEN updated
- THEN `GET /logos/{filename}` SHALL appear as public (no auth) with `ShowLogoAction`

### Requirement: Summary Table Updated

The system SHALL update the summary table reflecting the new endpoint count.
(Previously: summary counted 38 total endpoints)

#### Scenario: Count updated

- GIVEN 2 new logo endpoints are added
- WHEN the summary table is updated
- THEN the total SHALL increase by 2
- AND existing section counts SHALL remain accurate

#### Scenario: Count reconciliation

- GIVEN 40 endpoints are implemented in `routes/api.php`
- WHEN the summary table is updated
- THEN totals SHALL be: Health=1, Auth=6, Admin=19, Patients=4, Reports=9, Templates=1, Total=40
