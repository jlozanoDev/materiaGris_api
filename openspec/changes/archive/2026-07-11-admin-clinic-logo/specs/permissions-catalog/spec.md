# Delta for permissions-catalog

## MODIFIED Requirements

### Requirement: Clinic Permission Scope Documented

The system SHALL document that `admin.clinic.update` now covers logo upload in addition to clinic profile updates.
(Previously: `admin.clinic.update` only covered text-based `PUT /admin/clinic`)

#### Scenario: Permission scope updated

- GIVEN `docs/tecnica/modelo-permisos-roles.md` and `docs/funcional/modulos/administracion/permisos.md` document `admin.clinic.update`
- WHEN the logo upload feature is added
- THEN the permission entry SHALL note it covers both `PUT /admin/clinic` and `POST /admin/clinic/logo`
- AND no new permission slug is required
