# Módulo de Administración — Roles (API)

## Propósito de Negocio
Definir y gestionar los roles del sistema, asignando a cada rol un conjunto de permisos con concesión o denegación granular.

## Actores
- Administrador.

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/admin/roles` | GET | Listar todos los roles del sistema |
| `/api/admin/roles/{id}` | GET | Obtener un rol específico con sus permisos |
| `/api/admin/roles` | POST | Crear un nuevo rol |
| `/api/admin/roles/{id}` | PUT | Actualizar un rol (nombre, descripción, permisos) |
| `/api/admin/roles/{id}` | DELETE | Eliminar un rol |

## Criterios de Aceptación
- La creación debe permitir el slug único y la asignación inicial de permisos.
- La edición debe soportar la modificación de permisos con grant/deny.
- Roles del sistema (`is_system = true`) no deben ser eliminables.
- Al eliminar un rol, los usuarios pierden los permisos asociados a ese rol.

## Reglas de Negocio
- Slug único a nivel de sistema.
- `is_system` protege roles críticos contra eliminación.
- Deny (-1) en un rol prevalece sobre Grant (+1) de otro rol para el mismo permiso.
- Los permisos se asignan con valor: 1 = permitir, -1 = denegar.
- Los roles se organizan por categorías de permisos (admin, pacientes).

## Estructura de Datos

**POST /api/admin/roles — Request:**
```json
{
  "name": "Médico",
  "slug": "medico",
  "description": "Rol para médicos generales",
  "permissions": [
    { "permission_id": 10, "grant": 1 },
    { "permission_id": 11, "grant": 1 },
    { "permission_id": 12, "grant": 1 }
  ]
}
```

**GET /api/admin/roles — Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Administrador",
      "slug": "admin",
      "description": "Acceso total al sistema",
      "is_system": true,
      "permissions": [
        { "id": 1, "slug": "admin.user.view", "grant": 1 }
      ]
    }
  ]
}
```

## Dependencias
- Permisos: `admin.role.view`, `admin.role.create`, `admin.role.update`, `admin.role.delete`.
- Servicio `PermissionService` para validar asignaciones.

## Estado de Desarrollo
✅ Implementado — Completo (CRUD completo).

## Pendientes (Roadmap)
- Confirmación de eliminación con previsualización de usuarios afectados.
- Bloqueo visual de roles de sistema en el frontend.
- Historial de cambios en el rol.
