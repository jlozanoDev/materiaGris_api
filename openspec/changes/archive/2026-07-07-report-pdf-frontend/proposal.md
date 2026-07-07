# Proposal: Client-Side PDF Generation for Reports

## Intent

Replace server-side Dompdf generation with client-side PDF capture. The current flow renders an incomplete Blade template that ignores most field types, headers, footers, and system variables. The frontend already has a full-featured `ReportDocumentRenderer` with real variable resolution — but PDF generation and upload don't exist. This change builds that pipeline end-to-end.

## Scope

### In Scope
- Modify `ReportDocumentRenderer.vue` to accept a real `variableResolver` prop instead of hardcoded preview values
- Wrap renderer in a hidden export component (`ReportPdfExport.vue`) for off-screen A4 capture
- Integrate `html2pdf.js` to generate PDF blobs client-side
- Wire PDF generation into `useReportForm.ts.archive()` — generate blob before API call
- Modify `ApiReportRepository.archive()` to send `multipart/form-data` with the PDF file
- Update backend `ArchiveReportAction` + `ArchiveReportCommand` to receive, validate, and store the uploaded PDF instead of calling Dompdf
- Update `DownloadPdfReportCommand`: return 422 if `pdf_path` is missing (no regeneration)
- Update backend `ReportsCrudTest` with fake PDF fixture
- Document changes in `docs/tecnica/modules/reports/` and `docs/funcional/modulos/informes.md`

### Out of Scope
- Signature auto-injection: signature only appears if the template footer includes a signature field
- Backend clinic settings endpoint: clinic name stays hardcoded "Materia Gris"
- Dompdf cleanup: keep Dompdf installed for potential future use, but deactivate it in the flow
- Legacy report migration (none exist)

## Capabilities

### New Capabilities
- `report-pdf-frontend`: Client-side PDF generation pipeline — `html2pdf.js` capture, off-screen rendering, blob upload to backend during archive, and on-demand download for signed reports

### Modified Capabilities
- `reports-crud`: Archive endpoint now accepts `multipart/form-data` (PDF file) instead of generating server-side. PDF download endpoint returns 422 instead of regenerating when `pdf_path` is missing.

## Approach

**Flow**: ReportFillPage → ReportDocumentRenderer (with real data) → html2pdf.js capture → multipart upload to `POST /reports/{id}/archive` → backend validates & stores `storage/app/reports/`.

**Key technical decisions:**
- `ReportDocumentRenderer.vue` gains a `variableResolver` prop (function `(text) => string`), replacing internal `previewResolve`. By default it keeps preview behavior for the template builder.
- `ReportPdfExport.vue` is a hidden component that mounts `ReportDocumentRenderer` off-screen with real data for capture.
- Backend: `ArchiveReportAction` injects `Request $request`, validates file MIME (`application/pdf`), delegates to `ArchiveReportCommand` which stores to `storage/app/reports/` and updates `pdf_path`.
- `DownloadPdfReportCommand` removes regeneration logic — returns runtime error if file missing.
- For signed (non-archived) reports: frontend generates and downloads PDF client-side, no backend call.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/.../ReportDocumentRenderer.vue` | Modified | Accept `variableResolver` prop |
| `src/.../ReportPdfExport.vue` | New | Hidden export component with html2pdf.js |
| `src/.../useReportForm.ts` | Modified | Generate PDF blob before `archive()` |
| `src/.../ApiReportRepository.ts` | Modified | `archive(id, pdfBlob)` sends multipart |
| `src/.../ArchiveReportUseCase.ts` | Modified | Passes `pdfBlob` through to repository |
| `app/Http/Actions/Reports/ArchiveReportAction.php` | Modified | Accepts file upload via `Request` injection |
| `app/Commands/Reports/ArchiveReportCommand.php` | Modified | Stores uploaded PDF, removes Dompdf |
| `app/Commands/Reports/DownloadPdfReportCommand.php` | Modified | 422 on missing PDF (no regeneration) |
| `tests/.../ReportsCrudTest.php` | Modified | Fake PDF fixture for archive test |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `html2pdf.js` A4 rendering differs from preview | Med | Use fixed A4 dimensions; validate with a smoke test PDF |
| Large reports cause memory issues in browser | Low | A4 reports are text-heavy; profile with 20-page docs early |
| Backend file upload size limits | Low | Nginx default post body is 1MB; A4 text PDFs are ~50KB |
| PDF upload fails silently in slow networks | Low | Show loading state in archive button; surface errors |

## Rollback Plan

1. **Frontend**: Restore `archive()` to call API without file; remove `html2pdf.js`
2. **Backend**: Revert `ArchiveReportCommand` to generate PDF via Dompdf internally
3. **Database**: No schema changes — `pdf_path` column unchanged

## Dependencies

- `html2pdf.js` npm package (frontend)
- Phase order: 1 (renderer) → 2 (PDF gen) → 3 (upload) → 4 (backend receive) → 5 (tests) → 6 (docs)

## Success Criteria

- [ ] `ReportDocumentRenderer` resolves real patient/doctor/date variables via `SystemVariableRegistry`
- [ ] `ReportPdfExport` produces a valid A4 PDF with all field types rendered
- [ ] Archive flow: PDF blob is uploaded and stored server-side
- [ ] Download flow: signed report PDF is generated client-side and downloaded
- [ ] Backend tests pass with fake PDF fixture (archive endpoint)
- [ ] Docs updated: technical module doc, functional module doc, endpoint guide
