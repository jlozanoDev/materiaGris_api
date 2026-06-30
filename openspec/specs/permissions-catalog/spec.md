# permissions-catalog Specification

## Purpose

Expand `docs/tecnica/modelo-permisos-roles.md` and `docs/funcional/modulos/administracion/permisos.md` from 12 to 22+ documented permissions.

## Requirements

### Requirement: Report Permissions Added

The system SHALL document 6 `report.*` permission slugs in both the technical model and the functional catalog.

#### Scenario: Report permissions in seed data table

- GIVEN the permissions seed table lists 12 entries
- WHEN report permissions are added
- THEN these 6 SHALL appear: `report.view`, `report.create`, `report.edit`, `report.sign`, `report.close`, `report.download-pdf`
- AND each SHALL map to categories: `report.view`|`create` → `pacientes` or new `informes` category

#### Scenario: Report permissions in functional catalog

- GIVEN `docs/funcional/modulos/administracion/permisos.md` lists 12 permissions
- WHEN updated
- THEN the "Permisos del Sistema" table SHALL include all 6 `report.*` entries with slug, action, and category

### Requirement: Admin Report Template Permissions Added

The system SHALL document 4 `admin.reporttemplate.*` permission slugs: `view`, `create`, `update`, `delete`.

#### Scenario: Template admin permissions

- GIVEN the admin routes use `admin.reporttemplate.{view|create|update|delete}`
- WHEN permissions catalog is expanded
- THEN all 4 SHALL appear in both technical and functional docs

### Requirement: Missing View Permissions Added

The system SHALL document `admin.permission.view` and any other view permissions currently present in seeders but absent from docs.

#### Scenario: admin.permission.view documented

- GIVEN `GET /admin/permissions` uses `admin.permission.view`
- WHEN the seed data table is reviewed
- THEN `admin.permission.view` SHALL be listed explicitly if missing from current docs

### Requirement: Technical RBAC Doc Updated

The system SHALL update `/auth/me` response example in `modelo-permisos-roles.md` to include report and template permissions.

#### Scenario: Me endpoint example reflects all permissions

- GIVEN the `/auth/me` response example shows 7 permissions
- WHEN updated with new permissions
- THEN the example SHALL include at least 22 permission keys
