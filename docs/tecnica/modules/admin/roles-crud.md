# Admin — Roles: CRUD Técnico

## Rutas

| Método | URI | Permiso | Action |
|--------|-----|---------|--------|
| GET | `/api/admin/roles` | `admin.role.view` | `GetRolesAction` |
| GET | `/api/admin/roles/{id}` | `admin.role.view` | `GetRoleAction` |
| POST | `/api/admin/roles` | `admin.role.create` | `CreateRoleAction` |
| PUT | `/api/admin/roles/{id}` | `admin.role.update` | `UpdateRoleAction` |
| DELETE | `/api/admin/roles/{id}` | `admin.role.delete` | `DeleteRoleAction` |

## Actions

### `GetRolesAction`
- Invoca `GetRolesCommand`.
- Retorna todos los roles con sus permisos asociados.

### `GetRoleAction`
- Invoca `GetRoleCommand`.
- Retorna un rol específico con permisos y grant/deny.

### `CreateRoleAction`
- Valida: `name`, `slug` (único), `description`, `permissions` (array opcional).
- Invoca `CreateRoleCommand`.
- Crea el rol y asigna permisos iniciales.
- Responde `201`.

### `UpdateRoleAction`
- Valida: `name`, `description`, `permissions`.
- Invoca `UpdateRoleCommand`.
- Reemplaza el conjunto completo de permisos del rol.
- Invalida caché de permisos de todos los usuarios con ese rol.

### `DeleteRoleAction`
- Invoca `DeleteRoleCommand`.
- Verifica que `is_system = false`.
- Elimina el rol (los permisos asociados se pierden por CASCADE).
- Invalida caché de permisos de usuarios afectados.

## Commands

| Command | Descripción |
|---------|-------------|
| `GetRolesCommand` | Obtiene todos los roles con permisos |
| `GetRoleCommand` | Obtiene un rol por ID con permisos |
| `CreateRoleCommand` | Crea rol y asigna permisos |
| `UpdateRoleCommand` | Actualiza rol y reemplaza conjunto de permisos |
| `DeleteRoleCommand` | Elimina rol si `is_system = false` |

## Repositories

| Repositorio | Métodos principales |
|-------------|-------------------|
| `RoleRepository` | `getAll(): Collection`, `getById(int $id): ?Role`, `create(array $data): Role`, `update(int $id, array $data): Role`, `delete(int $id): void`, `syncPermissions(int $roleId, array $permissions): void` |

## Models

### `Role` — Tabla: `roles`

| Atributo | Tipo | Notas |
|----------|------|-------|
| `id` | bigint unsigned | PK |
| `name` | varchar(100) | Nombre legible |
| `slug` | varchar(100) | UNIQUE |
| `description` | text | NULLABLE |
| `is_system` | boolean | Protegido contra eliminación |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Relaciones: `permissions() (con grant)`, `users()`.

### `Permission` — Tabla: `permissions`

Relaciones: `category()`, `roles() (con grant)`.

## Flujo de Datos

### Actualizar permisos de un rol
```
PUT /api/admin/roles/{id}
  → AuthenticateJwt
  → RequirePermissions (admin.role.update)
  → UpdateRoleAction
    → UpdateRoleCommand
      → RoleRepository::update($id, $data)
      → RoleRepository::syncPermissions($id, $permissions)
      → PermissionService::invalidateCacheForAllUsers()
    ← Role
  ← 200
```

## Estado de Desarrollo

✅ Completo — CRUD implementado con sincronización de permisos e invalidación de caché.

## Pendientes

| Pendiente | Prioridad |
|-----------|-----------|
| Tests feature | Alta |
| Confirmación al eliminar roles con usuarios asignados | Media |
| Bloqueo en backend de eliminación de roles `is_system` (revisar) | Media |
