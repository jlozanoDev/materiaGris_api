# Templates Active Specification

## Purpose

`GET /api/templates/active` — returns all active report templates for the report creation picker. Protected by JWT (`auth.jwt`) and `report.create` permission.

## Requirements

### Requirement: Retrieve active templates

The system MUST return all active (non-soft-deleted) report templates ordered by `name` ascending, with full `structure` included, as a flat `data` array without pagination meta.

| Field | Type | Notes |
|-------|------|-------|
| `id` | int | |
| `name` | string | |
| `description` | string\|null | |
| `is_active` | bool | Always `true` in this response |
| `structure` | object\|array | JSON-cast; full sections/rows tree |
| `created_at` | string | ISO 8601 |
| `updated_at` | string | ISO 8601 |

#### Scenario: Authenticated professional with report.create permission

- GIVEN at least one active template exists
- AND user is authenticated with `report.create` permission
- WHEN `GET /api/templates/active`
- THEN status 200, body `{ "data": [...] }`
- AND templates are ordered by `name` ascending
- AND only `is_active = true` templates are returned
- AND each template includes full `structure`

#### Scenario: No active templates exist

- GIVEN no active templates exist (all inactive or soft-deleted)
- AND user is authenticated with `report.create`
- WHEN `GET /api/templates/active`
- THEN status 200, body `{ "data": [] }`

#### Scenario: Soft-deleted and inactive templates are excluded

- GIVEN templates exist with `is_active = false`
- AND soft-deleted templates exist (`deleted_at` not null)
- AND at least one active template exists
- WHEN `GET /api/templates/active`
- THEN only `is_active = true, deleted_at IS NULL` templates appear

### Requirement: Authentication required

The system MUST reject unauthenticated requests via `auth.jwt` middleware.

#### Scenario: No JWT cookie or Bearer token

- GIVEN no valid JWT
- WHEN `GET /api/templates/active`
- THEN status 401, body `{ "message": "Unauthorized" }`

#### Scenario: Invalid or revoked token

- GIVEN expired, tampered, or revoked JWT
- WHEN `GET /api/templates/active`
- THEN status 401, body `{ "message": "Invalid token" }`

### Requirement: report.create permission required

The system SHALL deny access when the authenticated user lacks `report.create`, checked at BOTH the middleware and command layers.

#### Scenario: User without report.create permission

- GIVEN authenticated user lacks `report.create`
- WHEN `GET /api/templates/active`
- THEN status 403, body `{ "message": "User lacks required permissions" }`

### Requirement: Internal error handling

Unexpected server errors SHALL produce a generic 500 without exposing internals, with logging.

#### Scenario: Unexpected exception during retrieval

- GIVEN authenticated user with `report.create`
- WHEN an unexpected error occurs in the repository or command
- THEN status 500, body `{ "message": "Internal server error" }`

## Data Contract

### Success Response (200)

```json
{
  "data": [
    {
      "id": 1,
      "name": "Informe General",
      "description": "Plantilla base para informes médicos",
      "is_active": true,
      "structure": {
        "sections": [
          { "title": "Anamnesis", "rows": [] }
        ]
      },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Error Responses

| Status | Condition | Body |
|--------|-----------|------|
| 200 | No active templates | `{ "data": [] }` |
| 401 | No token or invalid token | `{ "message": "Unauthorized" }` or `{ "message": "Invalid token" }` |
| 403 | User lacks `report.create` | `{ "message": "User lacks required permissions" }` |
| 500 | Unexpected server error | `{ "message": "Internal server error" }` |

## Non-Functional

- **Response time**: SHOULD return within 200ms under normal load (cached permissions, direct DB query)
- **Caching**: Permission checks are cached (60s TTL per user in `PermissionService`). Template data is NOT cached — always current.
- **TTD**: All scenarios above MUST be testable via SQLite in-memory + `RefreshDatabase`; JWT mocked via `JwtService` binding, permissions via `userPermissions` sync.
