# Módulo de Administración — Permisos (API)

## Propósito de Negocio
Proveer un catálogo legible de todos los permisos del sistema, organizados por categorías jerárquicas, para que los administradores puedan consultarlos y asignarlos a roles.

## Actores
- Administrador.

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/admin/permissions` | GET | Listar todos los permisos con sus categorías |

## Criterios de Aceptación
- La lista debe mostrar nombre, slug, acción y descripción de cada permiso.
- Los permisos deben estar agrupados por categoría.
- No debe ser posible crear, modificar o eliminar permisos vía API (se definen en migraciones).

## Reglas de Negocio
- Catálogo de solo lectura vía API.
- Los permisos se crean exclusivamente mediante migraciones de base de datos.
- Las categorías tienen estructura jerárquica (padre-hijo).
- Cada permiso tiene un slug único con formato `{categoria}.{accion}` (ej. `admin.user.view`).

## Estructura de Datos

**GET /api/admin/permissions — Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Ver Usuarios",
      "slug": "admin.user.view",
      "action": "view",
      "description": "Permite visualizar la lista de usuarios del sistema",
      "category": {
        "id": 1,
        "name": "Usuarios",
        "slug": "conf-users"
      }
    }
  ]
}
```

## Permisos del Sistema

| Slug | Acción | Categoría |
|------|--------|-----------|
| `admin.user.view` | view | Usuarios |
| `admin.user.create` | create | Usuarios |
| `admin.user.update` | update | Usuarios |
| `admin.user.delete` | delete | Usuarios |
| `admin.role.view` | view | Roles y Permisos |
| `admin.role.create` | create | Roles y Permisos |
| `admin.role.update` | update | Roles y Permisos |
| `admin.role.delete` | delete | Roles y Permisos |
| `admin.permission.view` | view | Roles y Permisos |
| `admin.reporttemplate.view` | view | Report Templates |
| `admin.reporttemplate.create` | create | Report Templates |
| `admin.reporttemplate.update` | update | Report Templates |
| `admin.reporttemplate.delete` | delete | Report Templates |
| `patient.view` | view | Pacientes |
| `patient.create` | create | Pacientes |
| `patient.update` | update | Pacientes |
| `report.view` | view | Informes |
| `report.create` | create | Informes |
| `report.edit` | update | Informes |
| `report.sign` | sign | Informes |
| `report.close` | close | Informes |
| `report.download-pdf` | download | Informes |

## Dependencias
- Permiso: `admin.permission.view`.
- Tabla `permission_categories` para agrupación jerárquica.

## Estado de Desarrollo
✅ Implementado — Completo.

## Pendientes (Roadmap)
- Visualización de categorías con estructura jerárquica completa.
- Indicador de permisos asignados a cada rol/usuario desde el catálogo.
