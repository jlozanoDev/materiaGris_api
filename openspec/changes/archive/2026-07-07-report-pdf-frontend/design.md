# Design: Client-Side PDF Generation for Reports

## Technical Approach

Replace server-side Dompdf with client-side `html2pdf.js` capture of the existing `ReportDocumentRenderer` component. The frontend already renders full A4 documents; this change wraps it in an off-screen capture container, generates a PDF blob, and uploads it to the backend during archive. The backend removes Dompdf generation and instead validates, stores, and serves the uploaded PDF.

## Architecture Decisions

| Decision | Options | Choice | Rationale |
|----------|---------|--------|-----------|
| Variable resolution | Pass resolver as prop vs. hardcode | `variableResolver?: (text) => string` prop with `previewResolve` as default | Backward-compatible with template builder; `ReportFillPage` already builds a real resolver |
| PDF capture container | Declarative in template vs. imperative mount | Imperative via `createVNode` + `render` | Avoids polluting the component tree; tear down after capture prevents memory leaks |
| Archive API body | JSON file vs. multipart | `multipart/form-data` with `pdf` field | `fetchClient` already supports FormData (skips Content-Type); backend receives via `$request->file('pdf')` |
| Download for signed reports | Backend download vs. client-side generation | Client-side `useReportPdf().generateBlob()` → browser download | No backend call needed; signed reports don't have a stored PDF yet |
| Dompdf removal | Delete package vs. deactivate in flow | Deactivate (keep installed, stop calling it) | Minimal blast radius; Dompdf stays for potential future Blade-only templates |
| Signature in PDF | Auto-inject vs. template-driven | Only if footer includes a signature field (user decision) | Respects existing template structure; `signatureUrl` prop conditionally renders |

## Data Flow

### Archive Flow
```
User clicks "Archivar"
  → useReportForm.archive()
  → buildPdfOptions(report, patient, authStore)  ← useReportPdf composable
  → generateReportPdf(options)                    ← imperatively mounts ReportPdfExport
       │
       ├─ createVNode(ReportPdfExport, { ...options, variableResolver })
       ├─ render(vnode, offscreenContainer)
       ├─ await nextTick() + 300ms render delay
       ├─ html2pdf().set({ margin: 20, filename, image: { quality: 0.95 } })
       │      .from(container.querySelector('.report-document'))
       │      .outputPdf('blob')
       ├─ render(null, container), removeChild  ← cleanup
       └─ return Blob
  → ApiReportRepository.archive(id, blob)
       └─ FormData.append('pdf', blob, 'informe_{id}.pdf')
       └─ POST /reports/{id}/archive  (multipart)
  → Backend: ArchiveReportAction
       └─ $request->file('pdf') validation → store to storage/app/reports/
       └─ repo.archivar(id, pdfPath) → status=archived, pdf_path set
  → Response → report.value = { ...report, ...updated }
```

### Download Flow (signed, not archived)
```
User clicks "Descargar PDF"
  → useReportForm.downloadPdf()
  → generateReportPdf(options)  ← same capture pipeline, no backend call
  → saveAs(blob, 'informe_{id}.pdf')
```

### Backend Download (archived)
```
GET /reports/{id}/pdf
  → DownloadPdfReportCommand → checks pdf_path exists
  → If missing → 422 "PDF no generado aún" (no regeneration)
  → If present → BinaryFileResponse with stored PDF
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/modules/admin/report-template/presentation/components/ReportDocumentRenderer.vue` | Modify | Add `variableResolver?: (text) => string` and `signatureUrl?: string` props; use resolver in `interpolateContent()`; render signature img in footer if field present |
| `src/modules/reports/presentation/components/ReportPdfExport.vue` | **Create** | Hidden A4 container wrapping `ReportDocumentRenderer` with passed props; no user interaction |
| `src/modules/reports/presentation/composables/useReportPdf.ts` | **Create** | `generateReportPdf(options) → Promise<Blob>`: imperative mount, `html2pdf.js` capture, cleanup |
| `src/modules/reports/presentation/composables/useReportForm.ts` | Modify | `archive()` builds PDF blob before calling use case; `downloadPdf()` branches: archived→backend, signed→client-side |
| `src/modules/reports/infrastructure/ApiReportRepository.ts` | Modify | `archive(id, blob)` sends `multipart/form-data` via FormData |
| `src/modules/reports/domain/use-cases/ArchiveReportUseCase.ts` | Modify | Accept `Blob` parameter, pass to repository |
| `src/modules/reports/domain/repositories/ReportRepository.ts` | Modify | `archive(id, pdfBlob)` signature change |
| `package.json` (frontend) | Modify | Add `html2pdf.js` dependency |
| `app/Http/Actions/Reports/ArchiveReportAction.php` | Modify | Inject `Request`, validate `pdf` file (required, mimes:pdf, max:10240), pass to command |
| `app/Commands/Reports/ArchiveReportCommand.php` | Modify | Accept `UploadedFile`, store to `storage/app/reports/report_{id}_{timestamp}.pdf`, remove Dompdf import/call |
| `app/Commands/Reports/DownloadPdfReportCommand.php` | Modify | Remove Dompdf regeneration block; throw `\RuntimeException('PDF no disponible')` if `pdf_path` missing |
| `tests/Feature/Actions/Reports/ReportsCrudTest.php` | Modify | Archive test uploads fake PDF (`UploadedFile::fake()->create('test.pdf', 100)`) |

## `html2pdf.js` Configuration

```js
html2pdf().set({
  margin:       20,           // 20mm all sides (matches .report-document padding)
  filename:     `informe_${id}.pdf`,
  image:        { type: 'jpeg', quality: 0.95 },
  html2canvas:  { scale: 2, useCORS: true },
  jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
  pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
})
```

A4 at 210×297mm with 20mm padding = 170×257mm content area. The `.report-document` CSS already sets `width: 210mm; padding: 20mm 20mm 25mm 20mm` — `html2canvas` captures this as-is. Tailwind classes compile to atomic CSS, fully preserved by html2canvas. CSS Grid in `rowStyle()` is natively supported by html2canvas. `page-break-inside: avoid` on rows prevents content splitting.

## Error Handling

| Scenario | Layer | Response |
|----------|-------|----------|
| PDF generation fails (html2pdf crash) | Frontend | Toast error, do NOT call backend |
| Upload network failure | Frontend | Surface "Error al subir PDF", offer retry |
| Backend: no file in request | Backend | 422 `{"message": "El archivo PDF es requerido"}` |
| Backend: wrong MIME type | Backend | 422 `{"message": "El archivo debe ser un PDF válido"}` |
| Backend: file >10MB | Backend | 422 `{"message": "El archivo PDF no debe exceder 10MB"}` |
| Backend: disk write failure | Backend | 500, logged |
| Download: pdf_path missing for archived | Backend | 422 `{"message": "PDF no generado aún"}` |

## Testing Strategy

| Layer | What | How |
|-------|------|-----|
| Backend Feature | Archive with PDF file upload | PHPUnit: `UploadedFile::fake()->create('test.pdf', 100)`, assert 200, verify `pdf_path` populated |
| Backend Feature | Archive without file | Assert 422 validation error |
| Backend Feature | Download when `pdf_path` null | Assert 422 with message |
| Frontend | Smoke: `npm run build` verifies no type errors | No frontend test runner; manual verification of PDF output |

## Open Questions

- None. All user decisions resolved.
