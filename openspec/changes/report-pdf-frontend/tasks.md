# Tasks: Client-Side PDF Generation for Reports

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~420 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Renderer + PDF capture + frontend integration + backend + tests + docs | Single PR | ~420 lines, borderline budget |

## Phase 1: Renderer Foundation

- [ ] 1.1 [frontend] Add `html2pdf.js` dependency: `npm install html2pdf.js` in `/home/j-loz/proyectos/MateriaGris_front`
- [ ] 1.2 [frontend] Modify `src/modules/admin/report-template/presentation/components/ReportDocumentRenderer.vue`: add `variableResolver?: (text: string) => string` and `signatureUrl?: string` props; update `interpolateContent()` to use `props.variableResolver ?? previewResolve`; render `${signatureUrl}` img in footer when `signatureUrl` is set
- [ ] 1.3 [frontend] Modify `src/modules/reports/domain/repositories/ReportRepository.ts`: change `archive(id, pdfBlob)` signature to accept `Blob` parameter

## Phase 2: PDF Capture Composable

- [ ] 2.1 [frontend] Create `src/modules/reports/presentation/composables/useReportPdf.ts`: `generateReportPdf(options) → Promise<Blob>` with imperative `createVNode`/`render` mount of `ReportPdfExport`, `html2pdf.js` capture (A4, 20mm margin, scale:2), cleanup via `render(null)`, MIME `application/pdf`
- [ ] 2.2 [frontend] Create `src/modules/reports/presentation/components/ReportPdfExport.vue`: hidden off-screen A4 wrapper (210mm width, `visibility: hidden`, `position: absolute`) mounting `ReportDocumentRenderer` with passed props

## Phase 3: Frontend Archive/Download Wiring

- [ ] 3.1 [frontend] Modify `src/modules/reports/infrastructure/ApiReportRepository.ts`: `archive(id, pdfBlob)` builds `FormData`, appends `pdf` field with blob, sends multipart POST (no Content-Type header — `fetchClient` handles)
- [ ] 3.2 [frontend] Modify `src/modules/reports/domain/use-cases/ArchiveReportUseCase.ts`: accept `Blob` parameter, pass to `reportRepository.archive(id, pdfBlob)`
- [ ] 3.3 [frontend] Modify `src/modules/reports/presentation/composables/useReportForm.ts`: `archive()` generates PDF blob via `useReportPdf()`, aborts on failure; `downloadPdf()` branches: signed report → `generateReportPdf()` + `saveAs`, archived → backend download (existing)

## Phase 4: Backend Multipart Handling

- [x] 4.1 [backend] Modify `app/Http/Actions/Reports/ArchiveReportAction.php`: inject `Request $request`, validate `pdf` file (required, mimes:pdf, max:10240), pass `UploadedFile` to command
- [x] 4.2 [backend] Modify `app/Commands/Reports/ArchiveReportCommand.php`: accept `UploadedFile` param to `execute()`, store to `storage/app/reports/report_{id}_{timestamp}.pdf` via `Storage::disk('local')->putFileAs()`, remove Dompdf import, `generatePdf()` method, and all Dompdf calls
- [x] 4.3 [backend] Modify `app/Commands/Reports/DownloadPdfReportCommand.php`: remove Dompdf regeneration block (lines 35-44); if `pdf_path` null or file missing, throw `\RuntimeException('PDF no generado aún')` for signed, model logic for archived

## Phase 5: Tests

- [x] 5.1 [backend] Modify `tests/Feature/Actions/Reports/ReportsCrudTest.php`: update `test_archive_updates_status_to_archived` to send multipart with `UploadedFile::fake()->create('test.pdf', 100)`; assert 200, `pdf_path` populated; add test for archive without file → 422; add test for download when `pdf_path` null → 422
- [x] 5.2 [backend] Run archive and full test suite — all ReportsCrudTest 22 passed, ArchiveReportActionTest 3 passed; 18 pre-existing failures unrelated

## Phase 6: Documentation

- [x] 6.1 [backend] Update `docs/tecnica/modules/reports/modulo-informes.md`: replace Dompdf references with client-side html2pdf.js generation; document archive endpoint multipart contract, download endpoint 422 behavior
- [x] 6.2 [backend] Update `docs/funcional/modulos/informes.md`: describe PDF client-side generation for signed reports, stored-file download for archived reports; remove Dompdf references
- [x] 6.3 [backend] Update `docs/tecnica/guia-endpoints-api.md`: POST /reports/{id}/archive now expects multipart/pdf file; GET /reports/{id}/pdf no longer regenerates
