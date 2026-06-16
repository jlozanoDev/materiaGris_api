# Patient Get-By-ID Specification

## Purpose

`GET /patients/{id}` — returns a single patient as a direct JSON object. Requires JWT authentication and `patient.view` permission.

## Requirements

### Requirement: Retrieve Patient by ID

The system MUST return the full patient record for an existing ID as a direct JSON object (no `data` wrapper).

**Fields**: All columns from `patients` table plus computed `age` (int|null) and `full_name` (string). Key fields:

| Field | Type | Constraint |
|-------|------|------------|
| `id` | int | |
| `gender` | string\|null | `"M"`, `"F"`, `"other"`, or `null` |
| `date_of_birth` | string\|null | `YYYY-MM-DD` |
| `last_visit_at` | string\|null | `YYYY-MM-DD` (date-only, stripped from timestamp) |
| `is_active` | bool | |
| `age` | int\|null | Computed from `date_of_birth` |
| `full_name` | string | `first_name last_name second_last_name`, trimmed |

**Contract rules**:
- Null columns MUST serialize as JSON `null` (never `""`).
- `last_visit_at` MUST drop time component; format as `YYYY-MM-DD`.
- `date_of_birth` SHALL be `YYYY-MM-DD` or `null`.

#### Scenario: Valid patient returns 200

- GIVEN patient `id=1` exists
- AND user is authenticated with `patient.view`
- WHEN `GET /patients/1`
- THEN status 200, body is a JSON object with all patient fields

#### Scenario: Patient with null fields returns null

- GIVEN patient exists with `last_visit_at=null` and `gender=null`
- AND user is authenticated with `patient.view`
- WHEN `GET /patients/{id}`
- THEN `last_visit_at` is `null` (not `""`), `gender` is `null`

### Requirement: Authentication Required

The system MUST reject unauthenticated requests.

#### Scenario: No JWT cookie

- GIVEN no valid JWT session cookie
- WHEN `GET /patients/1`
- THEN status 401, body `{"message": "No autenticado"}`

### Requirement: Permission Required

The system SHALL return 403 when the user lacks `patient.view`.

#### Scenario: User lacks patient.view

- GIVEN authenticated user without `patient.view`
- WHEN `GET /patients/1`
- THEN status 403, body `{"message": "No autorizado para ver este paciente"}`

### Requirement: Not Found Handling

The system MUST return 404 for non-existent patient IDs.

#### Scenario: Patient does not exist

- GIVEN user is authenticated with `patient.view`
- WHEN `GET /patients/99999`
- THEN status 404, body `{"message": "Paciente no encontrado"}`

### Requirement: Invalid ID Format

The route SHALL reject non-numeric IDs via `whereNumber` constraint.

#### Scenario: Non-numeric ID

- GIVEN user is authenticated with `patient.view`
- WHEN `GET /patients/abc`
- THEN status 404 (route constraint rejects non-numeric parameter)

### Requirement: Internal Error Handling

Unexpected exceptions SHALL produce a generic 500 without exposing internals.

#### Scenario: Unexpected exception

- GIVEN authenticated user with `patient.view`
- WHEN an unexpected error occurs during retrieval
- THEN status 500, body `{"message": "Error interno del servidor"}`
