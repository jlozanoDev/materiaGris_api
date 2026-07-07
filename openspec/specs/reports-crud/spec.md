# reports-crud Specification

## Purpose

Document the Reports CRUD module (8 endpoints) across technical, functional, and flow documentation layers.

## Requirements

### Requirement: Technical Module Documentation

The system SHALL create `docs/tecnica/modules/reports/modulo-informes.md` covering all 8 endpoints with Actions, Commands, Repositories, Models, middleware, data flow, error codes, and development status per the project's technical documentation prompt (`prompt-ia-documentacion-tecnica.md`).

#### Scenario: Module doc covers all endpoints

- GIVEN the route file `routes/api.php` defines 8 reports endpoints
- WHEN the technical module doc is written
- THEN it SHALL document: `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `POST /{id}/sign`, `POST /{id}/close`, `POST /{id}/archive`, `GET /{id}/pdf`
- AND each endpoint SHALL list: method, URI, middleware (`auth.jwt` + `require_permissions`), permission slug, Action class

#### Scenario: Model PatientReport is documented

- GIVEN the `PatientReport` model exists with fields: content, patient_id, template_id, status, signed_by, signed_at, archived_at, pdf_path
- WHEN the technical doc describes the data layer
- THEN the `PatientReport` model SHALL be listed with key attributes and relationships

### Requirement: Functional Module Documentation

The system SHALL create `docs/funcional/modulos/informes.md` with business purpose, actors, functionalities, acceptance criteria, business rules, data structure examples, and development status.

#### Scenario: Business rules for CRUD operations

- GIVEN the Reports module has CRUD operations
- WHEN the functional doc is written
- THEN it SHALL describe: report creation with patient+template selection, draft saving, signing workflow, closing workflow, PDF generation (client-side capture via html2pdf.js), PDF download (stored file or on-demand generation)

### Requirement: Flow Documentation

The system SHALL create `docs/funcional/flujos/gestion-informes.md` documenting the main flow (create → edit → sign → close → archive) and error flows (unauthorized, invalid report, concurrency conflicts).

#### Scenario: Main flow sequence is documented

- GIVEN a medical professional creating an inform
- WHEN the flow doc describes the sequence
- THEN it SHALL cover: init report → save draft → sign → close → archive (with client-side PDF upload)
- AND each step SHALL specify the API call, required permissions, and response format

### Requirement: Glossary Update

The system SHALL modify `docs/funcional/glosario-terminos.md` adding terms: Informe, Plantilla de Informe, Firma de Informe, Cierre de Informe.

#### Scenario: New glossary entries

- GIVEN the glossary has 21 entries
- WHEN reports terms are added
- THEN at least 4 new entries SHALL be present: "Informe", "Plantilla de Informe", "Firma de Informe", "Cierre de Informe"

### Requirement: Archive with Client-Generated PDF

`POST /reports/{id}/archive` MUST accept `multipart/form-data` with a `pdf` file field. The user MUST have `report.archive` permission and MUST be the report author. The report status MUST be `signed`. The backend SHALL validate the uploaded file as MIME `application/pdf` and SHALL enforce a 10MB maximum size. On success, the file SHALL be stored to `storage/app/reports/` and `pdf_path` updated on the report. No server-side PDF generation SHALL occur.

#### Scenario: Valid PDF archived successfully

- GIVEN a signed report owned by the authenticated user
- AND a `multipart/form-data` request with a valid `application/pdf` file in the `pdf` field
- WHEN `POST /reports/{id}/archive` is called
- THEN the server SHALL return 200 with the report data
- AND the PDF SHALL be stored at `storage/app/reports/`
- AND `pdf_path` SHALL point to the stored file

#### Scenario: Non-PDF file rejected

- GIVEN a signed report owned by the user
- WHEN `POST /reports/{id}/archive` is called with a non-PDF file in the `pdf` field
- THEN the server SHALL return 422 with a validation error

#### Scenario: Draft report rejected

- GIVEN a draft-status report owned by the user
- WHEN `POST /reports/{id}/archive` is called with a valid PDF
- THEN the server SHALL return 422 indicating the report must be signed

#### Scenario: Non-author rejected

- GIVEN a signed report NOT owned by the authenticated user
- WHEN `POST /reports/{id}/archive` is called
- THEN the server SHALL return 403

### Requirement: Download Stored PDF

`GET /reports/{id}/pdf` MUST serve a stored PDF file when `pdf_path` is set. The user MUST have `report.download-pdf` permission. If `pdf_path` is null and the report status is `signed`, the server SHALL return 422 with instructions for client-side generation. If `pdf_path` is null and the report is `archived`, the server SHALL return 404. No Dompdf regeneration SHALL occur.

#### Scenario: Stored PDF served for archived report

- GIVEN an archived report with a valid `pdf_path`
- WHEN `GET /reports/{id}/pdf` is called
- THEN the server SHALL stream the PDF file as `application/pdf`

#### Scenario: 422 for signed report without stored PDF

- GIVEN a signed report with null `pdf_path`
- WHEN `GET /reports/{id}/pdf` is called
- THEN the server SHALL return 422 with a body indicating client-side generation is required

#### Scenario: 404 for archived report with missing PDF

- GIVEN an archived report with null `pdf_path`
- WHEN `GET /reports/{id}/pdf` is called
- THEN the server SHALL return 404

### Requirement: Conditional Signature in PDF

The generated PDF SHOULD include a signature image only when the report template footer contains a signature placeholder field. The signature SHALL be rendered from `signature_data` (base64 data URL). Clinic name SHALL render as hardcoded "Materia Gris" via the system variable resolver.

#### Scenario: Signature rendered when footer has signature field

- GIVEN a template footer with a `{signature}` placeholder
- AND the report has non-null `signature_data` (base64)
- WHEN the PDF is generated client-side
- THEN the signature image SHALL appear in the footer area

#### Scenario: No signature when footer lacks signature field

- GIVEN a template footer without a `{signature}` placeholder
- WHEN the PDF is generated client-side
- THEN no signature SHALL appear
