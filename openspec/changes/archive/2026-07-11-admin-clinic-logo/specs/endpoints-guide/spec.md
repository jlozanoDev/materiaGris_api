# Delta for endpoints-guide

## ADDED Requirements

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

## MODIFIED Requirements

### Requirement: Summary Table Updated

The system SHALL update the summary table reflecting the new endpoint count.
(Previously: summary counted 38 total endpoints)

#### Scenario: Count updated

- GIVEN 2 new logo endpoints are added
- WHEN the summary table is updated
- THEN the total SHALL increase by 2
- AND existing section counts SHALL remain accurate
