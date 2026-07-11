# Clinic Logo Specification

## Purpose

Upload, store, and serve the institutional clinic logo. The system variable `{clinica.logo}` references this field in reports and templates.

## Requirements

### Requirement: Logo Upload

The system MUST accept multipart `POST /admin/clinic/logo` with a `logo` file field (PNG/JPEG/SVG/WebP, ≤5 MB). Auth: JWT + `admin.clinic.update`. Old logo SHALL be deleted before new one stored.

| Scenario | GIVEN | WHEN | THEN |
|----------|-------|------|------|
| Happy path | Auth user with permission | POST valid image | 200, `logo_url` set, file stored at `logos/{clinic_id}_{random}.{ext}` |
| Replacement | Clinic has existing logo | POST new logo | Old file deleted, new path stored, 200 |
| Unauthenticated | No JWT | POST | 401 |
| No permission | Auth user WITHOUT `admin.clinic.update` | POST | 403 |
| Invalid MIME | Auth user with permission | POST `application/pdf` | 415 |
| Oversized | Auth user with permission | POST file >5 MB | 413 |

### Requirement: Logo Serving

The system MUST serve logos via public `GET /logos/{filename}` — no auth. Response SHALL include `Content-Type` and `Access-Control-Allow-Origin: *`.

| Scenario | GIVEN | WHEN | THEN |
|----------|-------|------|------|
| File exists | Logo stored at `storage/app/public/logos/{filename}` | GET /logos/{filename} | 200, correct Content-Type, CORS header |
| File missing | No file at path | GET /logos/{filename} | 404 |

### Requirement: Clinic Response Extension

`GET /admin/clinic` response SHALL include `logo` field: absolute URL when set, `null` when no logo uploaded.

| Scenario | GIVEN | WHEN | THEN |
|----------|-------|------|------|
| Logo present | `clinics.logo` = `logos/1_a3f2.png` | GET /admin/clinic | `logo` = full URL |
| No logo | `clinics.logo` = NULL | GET /admin/clinic | `logo` = null |
