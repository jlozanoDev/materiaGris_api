# Módulo de Administración — Usuarios (API)

## Propósito de Negocio
Gestionar las cuentas de usuario del sistema: crear, consultar, modificar y eliminar usuarios, así como asignar roles y permisos individuales.

## Actores
- Administrador.

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/admin/users` | GET | Listar todos los usuarios del sistema |
| `/api/admin/users/{id}` | GET | Obtener un usuario específico con sus roles y permisos |
| `/api/admin/users` | POST | Crear un nuevo usuario |
| `/api/admin/users/{id}` | PUT | Actualizar datos de un usuario |
| `/api/admin/users/{id}` | DELETE | Eliminar (soft delete) un usuario |

## Criterios de Aceptación
- La creación debe permitir asignar roles y overrides de permisos.
- La actualización debe soportar cambio de contraseña, roles y overrides.
- La eliminación debe ser soft delete (marcar `deleted_at`).
- Usuarios del sistema (`is_system`) no deben ser eliminables.

## Reglas de Negocio
- Email único a nivel de sistema.
- Un usuario puede tener múltiples roles.
- Los overrides de permisos por usuario tienen prioridad sobre los del rol.
- Deny (-1) en override tiene prioridad sobre Grant (+1) del rol.
- Los usuarios marcados como sistema no pueden ser eliminados.
- El cambio de contraseña requiere confirmación de la contraseña actual.

## Estructura de Datos

**POST /api/admin/users — Request:**
```json
{
  "name": "Dr. López",
  "email": "drlopez@example.com",
  "password": "securepass123",
  "roles": [1],
  "permissions": {
    "patient.delete": -1
  }
}
```

**GET /api/admin/users — Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Dr. López",
      "email": "drlopez@example.com",
      "is_active": true,
      "roles": [
        { "id": 1, "name": "Administrador", "slug": "admin" }
      ]
    }
  ]
}
```

## Dependencias
- Permisos: `admin.user.view`, `admin.user.create`, `admin.user.update`, `admin.user.delete`.
- Servicio `RoleAssignmentService` para gestionar asignación de roles.
- Servicio `PermissionService` para overrides.

## Estado de Desarrollo
✅ Implementado — Completo (CRUD completo).

## Pendientes (Roadmap)
- Paginación en listado de usuarios.
- Filtros de búsqueda (por nombre, email, rol).
- Confirmación de eliminación con verificación de usuarios afectados.
- Historial de cambios en el usuario.
