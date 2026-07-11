# Delta for database-schema

## ADDED Requirements

### Requirement: Clinic Logo Column Documented

The system SHALL document the new `logo` column (nullable varchar) in the `clinics` table entry within `docs/tecnica/estructura-base-datos.md`.

#### Scenario: Logo column listed

- GIVEN the `clinics` table documentation exists in the DB doc
- WHEN the logo migration (`add_logo_to_clinics`) is applied
- THEN the clinics table entry SHALL list `logo` as nullable varchar
- AND the Clinic model reference SHALL include `logo` and `logo_url` accessor in its documented attributes
