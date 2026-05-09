# Admin — Permisos: Catálogo Técnico

## Rutas

| Método | URI | Permiso | Action |
|--------|-----|---------|--------|
| GET | `/api/admin/permissions` | `admin.permission.view` | `GetPermissionsAction` |

## Action

### `GetPermissionsAction`
- Invoca `GetPermissionsCommand`.
- Retorna todos los permisos del sistema agrupados por categoría.
- No requiere parámetros.
- Es solo lectura — los permisos se crean mediante migraciones.

## Command

| Command | Descripción |
|---------|-------------|
| `GetPermissionsCommand` | Obtiene todos los permisos con sus categorías |

## Repositories

| Repositorio | Métodos principales |
|-------------|-------------------|
| `GetPermissionRepository` | `getAll(): Collection` (con categorías eager loaded) |

## Models

### `Permission` — Tabla: `permissions`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `category_id` | bigint unsigned | FK → `permission_categories.id` |
| `name` | varchar(100) | Nombre legible |
| `slug` | varchar(150) | UNIQUE, formato `{categoria}.{accion}` |
| `action` | varchar(150) | view, create, update, delete |
| `description` | text | NULLABLE |

Relaciones: `category()`, `roles()`.

### `PermissionCategory` — Tabla: `permission_categories`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `parent_id` | bigint unsigned | NULLABLE, FK → self |
| `name` | varchar(100) | |
| `slug` | varchar(100) | UNIQUE |
| `order` | int | Orden de visualización |

Relaciones: `parent()`, `children()`, `permissions()`.

## Formato de Respuesta

```json
{
  "data": [
    {
      "id": 10,
      "name": "Ver Pacientes",
      "slug": "patient.view",
      "action": "view",
      "description": "Permite visualizar pacientes",
      "category": {
        "id": 5,
        "name": "Pacientes",
        "slug": "pacientes"
      }
    }
  ]
}
```

## Estado de Desarrollo

✅ Completo — Endpoint de listado implementado.

## Pendientes

| Pendiente | Prioridad |
|-----------|-----------|
| Tests feature | Media |
| Estructura jerárquica de categorías anidadas en respuesta | Baja |
