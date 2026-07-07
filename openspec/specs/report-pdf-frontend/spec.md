# report-pdf-frontend Specification

## Purpose

Client-side PDF generation for patient reports using `html2pdf.js` capture, real variable resolution via `SystemVariableRegistry`, off-screen A4 rendering, and integration with archive and download flows.

## Requirements

### Requirement: Variable Resolution in Report Renderer

`ReportDocumentRenderer.vue` MUST accept a `variableResolver` prop of type `(text: string) => string`. When provided, the resolver SHALL replace system variables (`{patientName}`, `{doctorName}`, `{date}`, `{clinic}`) with real values from `SystemVariableRegistry`. The clinic variable SHALL resolve to "Materia Gris". When the prop is omitted, the component SHALL fall back to preview placeholder values for the template builder.

#### Scenario: Real variables resolved from patient data

- GIVEN a report with patient "Juan Pérez", doctor "Dra. García", and today's date
- AND `ReportDocumentRenderer` receives a `variableResolver` using `SystemVariableRegistry`
- WHEN the component renders
- THEN `{patientName}` SHALL render as "Juan Pérez"
- AND `{clinic}` SHALL render as "Materia Gris"

#### Scenario: Preview fallback without resolver prop

- GIVEN `ReportDocumentRenderer` without a `variableResolver` prop
- WHEN the component renders
- THEN system variables SHALL render as preview placeholders (e.g., `[Nombre Paciente]`)

### Requirement: Off-Screen PDF Capture Component

`ReportPdfExport.vue` SHALL be a hidden component mounting `ReportDocumentRenderer` off-screen at fixed A4 width (210mm). It MUST expose a `generatePdf()` function returning `Promise<Blob>` with MIME type `application/pdf`, using `html2pdf.js` for capture. All template field types (text, checkbox, diagram, image) SHALL render.

#### Scenario: Valid A4 PDF blob produced

- GIVEN `ReportPdfExport` with valid report data, template, and variable resolver
- WHEN `generatePdf()` is called
- THEN the returned blob SHALL have MIME type `application/pdf`
- AND the blob SHALL be non-empty

#### Scenario: All field types rendered in PDF

- GIVEN a template containing text, checkbox, diagram, and image fields
- WHEN the PDF is generated
- THEN each field type SHALL be visually present in the output

### Requirement: Archive Flow Integration

`useReportForm.archive()` MUST generate a PDF blob via `ReportPdfExport` before calling the backend. On success, it SHALL call `ApiReportRepository.archive(id, pdfBlob)`, which sends a `multipart/form-data` POST with the `pdf` field. On generation failure, the archive SHALL abort and surface the error to the user.

#### Scenario: PDF generated and uploaded during archive

- GIVEN a signed report ready for archive
- WHEN the user triggers the archive action
- THEN a PDF blob SHALL be generated via `ReportPdfExport.generatePdf()`
- AND the blob SHALL be uploaded via `POST /reports/{id}/archive`
- AND on 200, the UI SHALL confirm the report is archived

#### Scenario: Archive aborted on generation failure

- GIVEN `ReportPdfExport.generatePdf()` throws an error
- WHEN the user triggers archive
- THEN the archive action SHALL NOT call the backend
- AND the error SHALL be surfaced in the UI

### Requirement: Signed Report Download Flow

For signed (non-archived) reports, the frontend SHALL generate a PDF client-side using `generateReportPdf()` and trigger a browser download. No backend PDF data endpoint SHALL be called for signed reports.

#### Scenario: Signed report PDF downloaded client-side

- GIVEN a signed report with null `pdf_path`
- WHEN the user triggers PDF download
- THEN the PDF SHALL be generated via `html2pdf.js` on the client
- AND the browser SHALL trigger a file download

### Requirement: Signature Rendering in PDF

The PDF generation MUST include a signature image when the report template footer defines a signature placeholder field. The signature SHALL render from `signature_data` (base64 data URL) on the report. If the template footer has no signature field, no signature SHALL appear.

#### Scenario: Signature rendered in PDF when configured

- GIVEN a report with `signature_data` (base64 PNG)
- AND the template footer includes a `{signature}` placeholder
- WHEN PDF is generated client-side
- THEN the signature image SHALL be visible in the PDF footer

#### Scenario: No signature rendered when not configured

- GIVEN a report template without a signature field in the footer
- WHEN PDF is generated
- THEN no signature SHALL appear in the output
