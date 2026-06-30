# Módulo de Informes — Documentación Técnica

> **Módulo**: Reports — CRUD de informes de pacientes
> **Documentación funcional**: [`docs/funcional/modulos/informes.md`](../../../funcional/modulos/informes.md)
> **Flujo de API**: [`docs/funcional/flujos/gestion-informes.md`](../../../funcional/flujos/gestion-informes.md)

## Rutas

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| GET | `/api/reports` | `auth.jwt`, `require_permissions:report.view` | `report.view` | `ListReportsAction` |
| POST | `/api/reports` | `auth.jwt`, `require_permissions:report.create` | `report.create` | `InitReportAction` |
| GET | `/api/reports/{id}` | `auth.jwt`, `require_permissions:report.view` | `report.view` | `GetReportAction` |
| PUT | `/api/reports/{id}` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `SaveDraftReportAction` |
| POST | `/api/reports/{id}/sign` | `auth.jwt`, `require_permissions:report.sign` | `report.sign` | `SignReportAction` |
| POST | `/api/reports/{id}/close` | `auth.jwt`, `require_permissions:report.close` | `report.close` | `CloseReportAction` |
| GET | `/api/reports/{id}/pdf` | `auth.jwt`, `require_permissions:report.download-pdf` | `report.download-pdf` | `DownloadPdfReportAction` |
| POST | `/api/reports/{id}/extract-data` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `ExtractReportDataAction` |
| POST | `/api/reports/{id}/transcribe` | `auth.jwt`, `require_permissions:report.edit` | `report.edit` | `TranscribeReportAction` |

## Actions

### `ListReportsAction`
- Invoca `ListReportsCommand` con filtros opcionales: `patient_id`, `status`, `patient` (búsqueda por nombre), `date_from`, `date_to`, `template_id`, `per_page`.
- Retorna paginación con `PatientReportResource`.
- **Response 200:** `{ data: PatientReportResource[], meta: { current_page, last_page, per_page, total } }`.

### `InitReportAction`
- Invoca `InitReportCommand` con datos validados por `InitReportRequest`.
- **Response 201:** Retorna el informe creado.
- Requiere `patient_id`, `template_id` y `template_structure_snapshot`.

### `GetReportAction`
- Invoca `GetReportCommand` con el ID del report.
- **Response 200:** Retorna el informe con datos del paciente, usuario y template.
- **404:** Si el ID no existe.

### `SaveDraftReportAction`
- Invoca `SaveDraftReportCommand` con datos validados por `SaveDraftReportRequest`.
- Permite actualizar los valores del contenido del informe.
- **Response 200:** Retorna el informe actualizado.
- **422:** Si el informe no está en estado `draft`.

### `SignReportAction`
- Invoca `SignReportCommand` con datos validados por `SignReportRequest` (incluye `signature` en base64).
- Almacena la firma como imagen PNG en `storage/app/signatures/`.
- **Response 200:** Retorna el informe firmado.
- **422:** Si el informe no está en estado `draft` o la firma es inválida.

### `CloseReportAction`
- Invoca `CloseReportCommand` — genera PDF del informe firmado y lo almacena.
- **Response 200:** Retorna el informe cerrado con `pdf_path`.
- **422:** Si el informe no está en estado `signed`.

### `DownloadPdfReportAction`
- Invoca `DownloadPdfReportCommand` — descarga el PDF generado.
- **Response:** `BinaryFileResponse` con `Content-Type: application/pdf`.
- **422:** Si el informe no está firmado ni cerrado.

### `ExtractReportDataAction`
- Ver `modulo-dictado-autocompletado.md` para detalles completos.

### `TranscribeReportAction`
- Ver `modulo-dictado-autocompletado.md` para detalles completos.

## Requests

| Request Class | Endpoint | Campos clave |
|--------------|----------|--------------|
| `ListReportsRequest` | GET /reports | `patient_id`, `status`, `patient`, `date_from`, `date_to`, `template_id`, `per_page` |
| `InitReportRequest` | POST /reports | `patient_id` (required), `template_id` (required), `template_structure_snapshot` (required, JSON) |
| `SaveDraftReportRequest` | PUT /reports/{id} | `values` (required, JSON) — contenido del informe |
| `SignReportRequest` | POST /reports/{id}/sign | `signature` (required, string base64) |

## Commands (Use Cases)

### Reports CRUD

| Command | Método `execute` | Lógica clave |
|---------|------------------|--------------|
| `ListReportsCommand` | `(array $filters): LengthAwarePaginator` | Verifica `report.view`, delega a `PatientReportReadRepository::listar()` |
| `InitReportCommand` | `(array $data): PatientReport` | Verifica `report.create`, delega a `PatientReportSaveRepository::iniciar()` |
| `GetReportCommand` | `(int $id): PatientReport` | Verifica `report.view`, busca por ID |
| `SaveDraftReportCommand` | `(int $id, array $data): PatientReport` | Verifica `report.edit`, valida que sea el autor, valida estado `draft` |
| `SignReportCommand` | `(int $id, array $data): PatientReport` | Verifica `report.sign`, valida autoría y estado `draft`, almacena firma en base64 como PNG |
| `CloseReportCommand` | `(int $id): PatientReport` | Verifica `report.close`, valida autoría y estado `signed`, genera PDF con `DomPDF` |
| `DownloadPdfReportCommand` | `(int $id): PdfFileInfo` | Verifica `report.download-pdf`, regenera PDF si falta `pdf_path` |

### Ciclo de vida de estados

```
draft ──sign──▶ signed ──close──▶ closed
  │                                  │
  └── edit (solo en draft)           └── download PDF
```

## Repositories

### `PatientReportReadRepository`
- `listar(array $filters): LengthAwarePaginator` — lista paginada con filtros por `patient_id`, `status`, `patient` (nombre), `date_from`, `date_to`, `template_id`. Orden descendente por `created_at`. Incluye relaciones `patient`, `user`, `template`.
- `buscarPorId(int $id): ?PatientReport` — búsqueda por ID con relaciones.

### `PatientReportSaveRepository`
- `iniciar(array $data): PatientReport` — crea un nuevo informe.
- `actualizarValores(int $id, array $values): PatientReport` — actualiza los valores del contenido.
- `firmar(int $id, string $signaturePath): PatientReport` — firma el informe, actualiza `signature_path` y `signed_at`.
- `cerrar(int $id, string $pdfPath): PatientReport` — cierra el informe, actualiza `pdf_path` y `closed_at`.

## Modelos

### `PatientReport` — Tabla: `patient_reports`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `patient_id` | bigint unsigned | FK → `patients.id` |
| `user_id` | bigint unsigned | FK → `users.id` |
| `template_id` | bigint unsigned | NULLABLE, FK → `report_templates.id` ON DELETE SET NULL |
| `status` | string(255) | DEFAULT `'draft'`. Valores: `draft`, `signed`, `closed` |
| `template_structure_snapshot` | json | NO NULL — copia de la estructura de la plantilla al momento de creación |
| `values` | json | DEFAULT `'{}'` — valores del contenido del informe |
| `signature_path` | string(255) | NULLABLE — ruta a la imagen de la firma |
| `pdf_path` | string(255) | NULLABLE — ruta al PDF generado |
| `signed_at` | timestamp | NULLABLE |
| `closed_at` | timestamp | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Índices:** `patient_id`, `user_id`, `status`, `(patient_id, status)`.

**Modelo:** `App\Models\PatientReport` — `$casts: ['status' => ReportStatus::class, 'values' => 'array', 'template_structure_snapshot' => 'array']`.

**Relaciones:**
- `patient()` → `BelongsTo: Patient`
- `user()` → `BelongsTo: User`
- `template()` → `BelongsTo: ReportTemplate` (con `withTrashed`)

### `ReportTemplate` — Tabla: `report_templates`

Ver `modulo-plantillas.md` para detalles completos.

### `LlmInteraction` — Tabla: `llm_interactions`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `patient_report_id` | bigint unsigned | FK → `patient_reports.id` ON DELETE CASCADE |
| `type` | string(50) | DEFAULT `'extraction'`. Valores: `extraction`, `stt` |
| `request_payload` | json | NO NULL |
| `response_payload` | json | NULLABLE |
| `processing_time_ms` | integer | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

## Códigos de Error

| Código | Cuándo ocurre |
|--------|---------------|
| 403 | Sin permiso requerido (`report.view`, `report.create`, `report.edit`, `report.sign`, `report.close`, `report.download-pdf`) |
| 404 | Informe no encontrado |
| 422 | Validación fallida / estado incorrecto (ej. firmar un informe no-draft) |
| 500 | Error interno del servidor |

## Flujo de Datos (Ciclo Completo)

```
POST /api/reports (InitReport)
  → AuthenticateJwt
  → RequirePermissions (report.create)
  → InitReportAction → InitReportCommand
    → PermissionService::ensure('report.create')
    → PatientReportSaveRepository::iniciar({ patient_id, template_id, template_structure_snapshot })
    ← 201 { report }

PUT /api/reports/{id} (SaveDraftReport)
  → auth.jwt → require_permissions:report.edit
  → SaveDraftReportAction → SaveDraftReportCommand
    → PermissionService::ensure('report.edit')
    → Validar: status === draft, user === author
    → PatientReportSaveRepository::actualizarValores(id, values)
    ← 200 { report }

POST /api/reports/{id}/sign
  → auth.jwt → require_permissions:report.sign
  → SignReportAction → SignReportCommand
    → PermissionService::ensure('report.sign')
    → Validar: status === draft, user === author
    → Store signature base64 as PNG
    → PatientReportSaveRepository::firmar(id, signaturePath)
    ← 200 { report }

POST /api/reports/{id}/close
  → auth.jwt → require_permissions:report.close
  → CloseReportAction → CloseReportCommand
    → PermissionService::ensure('report.close')
    → Validar: status === signed, user === author
    → DomPDF::loadView('reports.pdf', report)
    → PatientReportSaveRepository::cerrar(id, pdfPath)
    ← 200 { report }

GET /api/reports/{id}/pdf
  → auth.jwt → require_permissions:report.download-pdf
  → DownloadPdfReportAction → DownloadPdfReportCommand
    → PermissionService::ensure('report.download-pdf')
    → Validar: status === signed || status === closed
    → Regenerar PDF si falta pdf_path
    ← BinaryFileResponse (PDF)
```

## Dependencias

- **Permisos:** `report.view`, `report.create`, `report.edit`, `report.sign`, `report.close`, `report.download-pdf`
- **Modelos relacionados:** `Patient`, `User`, `ReportTemplate`
- **Librerías externas:** `barryvdh/laravel-dompdf` (generación de PDF)

## Estado de Desarrollo

✅ Completo — 9 endpoints implementados, reports CRUD funcional, ciclo draft→sign→close implementado, PDF con DomPDF, extract-data con IA, transcribe con STT.
