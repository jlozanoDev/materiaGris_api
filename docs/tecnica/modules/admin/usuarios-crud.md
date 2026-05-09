# Admin — Usuarios: CRUD Técnico

## Rutas

| Método | URI | Permiso | Action |
|--------|-----|---------|--------|
| GET | `/api/admin/users` | `admin.user.view` | `UserAction` |
| GET | `/api/admin/users/{id}` | `admin.user.view` | `GetUserAction` |
| POST | `/api/admin/users` | `admin.user.create` | `CreateUserAction` |
| PUT | `/api/admin/users/{id}` | `admin.user.update` | `UpdateUserAction` |
| DELETE | `/api/admin/users/{id}` | `admin.user.delete` | `DeleteUserAction` |

## Actions

### `UserAction` (listar)
- Invoca `GetAllUsersCommand`.
- Retorna lista de usuarios con roles asignados.
- **Pendiente:** Paginación.

### `GetUserAction`
- Invoca `GetUserCommand`.
- Retorna usuario con roles, overrides y permisos efectivos.

### `CreateUserAction`
- Valida con `CreateUserRequest`: `name`, `email` (único), `password`, `roles` (array), `permissions` (object opcional).
- Invoca `CreateUserCommand`.
- Asigna roles y overrides vía `RoleAssignmentService`.
- Responde `201`.

### `UpdateUserAction`
- Valida con `UpdateUserRequest`: `name`, `email`, `password` (opcional), `roles`, `permissions`.
- Invoca `UpdateUserCommand`.
- Reasigna roles y overrides.
- Invalida caché de permisos del usuario.

### `DeleteUserAction`
- Invoca `DeleteUserCommand`.
- Marca `deleted_at` (soft delete).
- Verifica que el usuario no sea de sistema.

## FormRequests

### `CreateUserRequest`
```php
'name' => 'required|string|max:255',
'email' => 'required|email|unique:users,email',
'password' => 'required|string|min:8',
'roles' => 'sometimes|array',
'roles.*' => 'exists:roles,id',
'permissions' => 'sometimes|array',
```

### `UpdateUserRequest`
```php
'name' => 'sometimes|string|max:255',
'email' => 'sometimes|email|unique:users,email,' . $this->route('id'),
'password' => 'sometimes|string|min:8',
'roles' => 'sometimes|array',
'roles.*' => 'exists:roles,id',
'permissions' => 'sometimes|array',
```

## Commands

| Command | Descripción |
|---------|-------------|
| `GetAllUsersCommand` | Obtiene todos los usuarios con sus roles |
| `GetUserCommand` | Obtiene usuario por ID con roles y permisos |
| `CreateUserCommand` | Crea usuario, asigna roles y overrides |
| `UpdateUserCommand` | Actualiza datos, roles y overrides |
| `DeleteUserCommand` | Soft delete, verifica que no sea de sistema |

## Repositories

| Repositorio | Métodos principales |
|-------------|-------------------|
| `GetUserRepository` | `getAll(): Collection`, `getById(int $id): ?User` |
| `SaveUserRepository` | `create(array $data): User`, `update(int $id, array $data): User`, `delete(int $id): void` |

## Model

### `User` — Tabla: `users`

Atributos principales: `id`, `name`, `email`, `password`, `deleted_at` (soft delete).

Relaciones: `roles()`, `permissions()`, `addresses()`, `effectivePermissions()`.

## Servicios Relacionados

- `RoleAssignmentService` — asigna/revoca roles a usuarios.
- `PermissionService` — aplica overrides e invalida caché.

## Flujo de Datos

### Crear usuario
```
POST /api/admin/users
  → AuthenticateJwt
  → RequirePermissions (admin.user.create)
  → CreateUserAction
    → CreateUserRequest (validación)
    → CreateUserCommand
      → SaveUserRepository::create($data)
      → RoleAssignmentService::assignRoles($user, $roles)
      → PermissionService::applyOverrides($user, $permissions)
    ← User
  ← 201
```

## Estado de Desarrollo

✅ Completo — CRUD implementado con validación, asignación de roles y overrides.

## Pendientes

| Pendiente | Prioridad |
|-----------|-----------|
| Paginación en listado | Alta |
| Filtros de búsqueda por nombre, email, rol | Media |
| Tests feature | Alta |
| Notificación de confirmación en eliminación | Baja |
