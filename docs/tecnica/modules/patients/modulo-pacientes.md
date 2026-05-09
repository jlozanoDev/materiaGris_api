# Módulo de Pacientes — Documentación Técnica

## Rutas

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| GET | `/api/patients/find` | `auth.jwt`, `require_permissions:patient.view` | `patient.view` | `GetPatientsAction` |
| POST | `/api/patients` | `auth.jwt`, `require_permissions:patient.create` | `patient.create` | `CreatePatientAction` |
| PUT | `/api/patients/{id}` | `auth.jwt`, `require_permissions:patient.update` | `patient.update` | `UpdatePatientAction` |

## Actions

### `GetPatientsAction`
- Parámetros query: `search`, `gender`, `is_active`, etc.
- Busca pacientes por múltiples criterios.
- Responde con array de pacientes (datos básicos + `age` y `full_name` calculados).

### `CreatePatientAction`
- Valida datos del paciente (unicidad de NSS, email).
- Crea el paciente en BD.
- Responde `201 Created`.

### `UpdatePatientAction`
- Busca paciente por ID.
- Valida unicidad de NSS/email excluyendo el ID actual.
- Actualiza y responde `200`.

## Commands (Use Cases)

**Nota:** Actualmente no existen Commands dedicados para pacientes. La lógica está directamente en las Actions. Esto es una inconsistencia arquitectónica.

| Comando | Estado | Descripción |
|---------|--------|-------------|
| `GetPatientsCommand` | ❌ No existe | Lógica de búsqueda en Action |
| `CreatePatientCommand` | ❌ No existe | Lógica de creación en Action |
| `UpdatePatientCommand` | ❌ No existe | Usa `UpdateUserCommand` de Admin |

## Repositories

| Repositorio | Métodos principales |
|-------------|-------------------|
| `PatientReadRepository` | `findByFilters(array $filters): Collection` |
| `SavePatientRepository` | `create(array $data): Patient`, `update(int $id, array $data): Patient` |

## Model

### `Patient` — Tabla: `patients`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `medical_record_number` | string | NULLABLE, UNIQUE |
| `national_id` | string | NULLABLE, UNIQUE |
| `first_name`, `last_name`, `second_last_name` | string | INDEX |
| `gender` | string | INDEX |
| `date_of_birth` | date | INDEX |
| `email` | string | UNIQUE, INDEX |
| `phone`, `mobile` | string | INDEX |
| `is_active` | boolean | INDEX, default true |
| `last_visit_at` | timestamp | NULLABLE |
| `address_line1`, `address_line2` | string | NULLABLE |
| `neighborhood`, `city`, `state`, `country`, `postal_code` | string | INDEX |
| `contact_name`, `contact_phone` | string | NULLABLE |
| `age` | *(appended)* | Calculado desde `date_of_birth` |
| `full_name` | *(appended)* | `first_name + last_name + second_last_name` |

## Flujo de Datos

### Buscar pacientes
```
GET /api/patients/find?search=Juan
  → AuthenticateJwt
  → RequirePermissions (patient.view)
  → GetPatientsAction
    → PatientReadRepository::findByFilters(['search' => 'Juan'])
    ← Collection<Patient>
  ← 200 { data: [...] }
```

### Crear paciente
```
POST /api/patients
  → AuthenticateJwt
  → RequirePermissions (patient.create)
  → CreatePatientAction
    → Valida request
    → SavePatientRepository::create($data)
    ← Patient
  ← 201 { data: { ... } }
```

## Estado de Desarrollo

⚠️ Parcial — Implementado: buscar, crear, actualizar.

## Pendientes (Roadmap)

| Pendiente | Prioridad |
|-----------|-----------|
| Endpoint DELETE con soft delete | Alta |
| Extraer Commands dedicados para pacientes | Alta |
| Paginación en búsqueda | Alta |
| Crear FormRequests: `StorePatientRequest`, `UpdatePatientRequest` | Media |
| Tests feature para pacientes | Media |
| Ordenamiento por columnas en listados | Baja |
