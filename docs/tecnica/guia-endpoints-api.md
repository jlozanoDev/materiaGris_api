# Guía de Endpoints — MateriaGris API

## Base URL

Todas las rutas están prefijadas con `/api`.

```
http://localhost:8000/api
```

---

## Health

| Método | URI | Middleware | Permiso | Action | Descripción |
|--------|-----|-----------|---------|--------|-------------|
| GET | `/api/health` | — | — | `CheckHealthAction` | Verificar estado del servidor |

---

## Auth

| Método | URI | Middleware | Permiso | Action | Descripción |
|--------|-----|-----------|---------|--------|-------------|
| POST | `/api/auth/login` | `throttle:5,1` | — | `LoginAction` | Iniciar sesión |
| POST | `/api/auth/refresh` | — | — | `RefreshAction` | Renovar JWT |
| POST | `/api/auth/logout` | — | — | `LogoutAction` | Cerrar sesión |
| GET | `/api/auth/me` | `auth.jwt` | — | `MeAction` | Perfil + permisos |
| POST | `/api/auth/forgot` | `throttle:5,1` | — | `ForgotPasswordAction` | Solicitar reset |
| POST | `/api/auth/reset` | — | — | `ResetPasswordAction` | Resetear contraseña |

---

## Admin

Todas las rutas de administración requieren `auth.jwt`.

### Permisos

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| GET | `/api/admin/permissions` | `admin.permission.view` | `GetPermissionsAction` | Catálogo de permisos |

### Usuarios

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| GET | `/api/admin/users` | `admin.user.view` | `UserAction` | Listar usuarios |
| GET | `/api/admin/users/{id}` | `admin.user.view` | `GetUserAction` | Obtener usuario |
| POST | `/api/admin/users` | `admin.user.create` | `CreateUserAction` | Crear usuario |
| PUT | `/api/admin/users/{id}` | `admin.user.update` | `UpdateUserAction` | Actualizar usuario |
| DELETE | `/api/admin/users/{id}` | `admin.user.delete` | `DeleteUserAction` | Eliminar usuario |

### Roles

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| GET | `/api/admin/roles` | `admin.role.view` | `GetRolesAction` | Listar roles |
| GET | `/api/admin/roles/{id}` | `admin.role.view` | `GetRoleAction` | Obtener rol |
| POST | `/api/admin/roles` | `admin.role.create` | `CreateRoleAction` | Crear rol |
| PUT | `/api/admin/roles/{id}` | `admin.role.update` | `UpdateRoleAction` | Actualizar rol |
| DELETE | `/api/admin/roles/{id}` | `admin.role.delete` | `DeleteRoleAction` | Eliminar rol |

### Variables del Sistema

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| GET | `/api/admin/system-variables` | — (solo `auth.jwt`) | `GetSystemVariablesAction` | Catálogo de variables para plantillas de informe |

---

## Reports

Todas las rutas requieren `auth.jwt`.

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| POST | `/api/reports/{id}/extract-data` | `report.edit` | `ExtractReportDataAction` | Extraer datos clínicos desde transcripción con IA |

### POST /reports/{id}/extract-data

Recibe una transcripción de audio y una plantilla, extrae datos clínicos estructurados usando un LLM.

**Request Body:**
```json
{
  "transcript": "string (requerido)",
  "template_id": "integer (requerido)"
}
```

**Response 200:**
```json
{
  "data": {
    "extracted_data": { "field_key": "value", ... },
    "confidence_scores": { "field_key": 0.95, ... },
    "warnings": ["Campo 'alergias' no encontrado"],
    "processing_time_ms": 1250
  },
  "meta": {},
  "message": "Datos extraídos correctamente"
}
```

**Error codes:** 400 (template inválido), 403 (sin permiso), 404 (report no encontrado), 422 (validación), 500 (error LLM), 503 (IA no disponible)

---

## Patients

Todas las rutas requieren `auth.jwt`.

| Método | URI | Permiso | Action | Descripción |
|--------|-----|---------|--------|-------------|
| GET | `/api/patients/find` | `patient.view` | `GetPatientsAction` | Buscar pacientes |
| POST | `/api/patients` | `patient.create` | `CreatePatientAction` | Crear paciente |
| PUT | `/api/patients/{id}` | `patient.update` | `UpdatePatientAction` | Actualizar paciente |

---

## Resumen

| Categoría | Endpoints | Autenticados | Con permisos |
|-----------|-----------|-------------|--------------|
| Health | 1 | 0 | 0 |
| Auth | 6 | 1 | 0 |
| Admin | 12 | 12 | 11 |
| Patients | 3 | 3 | 3 |
| Reports | 1 | 1 | 1 |
| **Total** | **23** | **17** | **15** |

## Middlewares

| Alias | Clase | Función |
|-------|-------|---------|
| `auth.jwt` | `AuthenticateJwt` | Valida JWT Bearer token, establece `Auth::user()` |
| `require_permissions:{slugs}` | `RequirePermissions` | Verifica permisos del usuario. Modo: `all` (default) o `any` |
| `throttle:{x,y}` | Laravel default | Limita a `x` intentos por `y` minutos |

## Formato de Respuesta de Error

```json
{
  "error": "Unauthorized",
  "message": "Missing required permission: admin.user.view"
}
```

Códigos HTTP utilizados:
- `200` — Éxito
- `201` — Creado
- `204` — Sin contenido (eliminación)
- `401` — No autenticado / Permiso denegado
- `404` — Recurso no encontrado
- `422` — Error de validación
- `429` — Demasiadas solicitudes (rate limit)
- `500` — Error interno del servidor
