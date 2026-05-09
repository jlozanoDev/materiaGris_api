# Modelo de Permisos y Roles — MateriaGris API

## Esquema de Base de Datos

El sistema RBAC utiliza 6 tablas principales:

### `roles`
| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint unsigned | PK |
| `name` | varchar(100) | Nombre legible |
| `slug` | varchar(100) | Identificador único |
| `description` | text | Opcional |
| `is_system` | tinyint(1) | Protegido contra eliminación |

### `permissions`
| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint unsigned | PK |
| `category_id` | bigint unsigned | FK → `permission_categories.id` |
| `name` | varchar(100) | Nombre legible |
| `slug` | varchar(150) | Identificador único (ej. `patient.view`) |
| `action` | varchar(150) | Acción (view, create, update, delete) |
| `description` | text | Opcional |

### `permission_categories`
Agrupación jerárquica de permisos. Soporta `parent_id` para subcategorías.

### `role_permissions`
Pivote entre roles y permisos con columna `grant`:
- `grant = 1` → permitir
- `grant = -1` → denegar

### `user_roles`
Asignación de roles a usuarios. Soporta `assigned_by`, `assigned_at`, `revoked_at` y `meta` (JSON).

### `user_permissions`
Overrides directos por usuario, con seguimiento de origen:
- `origin = 'role'` → heredado de un rol (`origin_id` apunta al rol)
- `origin = 'user'` → asignado directamente por un administrador
- `grant = 1` → permitir, `grant = -1` → denegar

### `user_effective_permissions`
Tabla de materialización. Almacena el permiso efectivo calculado para cada usuario tras evaluar roles + overrides. `grant` puede ser 1, -1 o 0 (neutral).

## Principios

### Deny-Overrides
Una denegación explícita (`-1`) tiene prioridad sobre cualquier concesión (`+1`), tanto a nivel de rol como de override directo.

Jerarquía de resolución:
1. Override directo del usuario con `grant = -1` → **Denegado**
2. Override directo del usuario con `grant = 1` → **Permitido**
3. Permiso del rol con `grant = -1` → **Denegado**
4. Permiso del rol con `grant = 1` → **Permitido**
5. Sin registro → **Denegado por defecto**

### Caché
Los permisos efectivos se almacenan en `user_effective_permissions` y se invalidan mediante `permissions_version` (timestamp) cuando se modifican roles, asignaciones o overrides.

## Servicios

### `PermissionService`
- `userHasPermission(User $user, string $slug): bool` — verifica permiso efectivo.
- `ensure(User $user, string $slug): void` — lanza `PermissionDeniedException` si no tiene permiso.
- `getEffectivePermissions(User $user): array` — obtiene mapa completo {slug → bool}.
- `invalidateCacheForUser(int $userId): void` — invalida caché de permisos.
- `invalidateCacheForAllUsers(): void` — invalida todas las cachés.

### `AuditService`
- `record(int $actorId, string $event, array $metadata = []): void` — persiste en tabla `audits`.
- Registra eventos como `policy.denied`, cambios de roles, creación de usuarios, etc.
- Los registros son inmutables (append-only).

### `RoleAssignmentService`
- Gestiona la asignación y revocación de roles a usuarios.
- Coordina con `PermissionService` la invalidación de caché.

## Middleware

### `RequirePermissions`
```php
// Uso en rutas:
Route::get('/admin/users', ...)->middleware('require_permissions:admin.user.view');
Route::post('/admin/users', ...)->middleware('require_permissions:admin.user.create|admin.user.update', 'any');

// Modo: 'all' (default) requiere todos los permisos listados
// Modo: 'any' requiere al menos uno
```

- Si el usuario no tiene el permiso: registra `policy.denied` en auditoría y responde `401`.
- Se usa `401` (no `403`) por decisión del equipo, unificando con JWT expirado.

## Contrato `/api/auth/me`

```json
{
  "id": 1,
  "name": "Dr. García",
  "email": "medico@example.com",
  "roles": ["admin"],
  "permissions": {
    "admin.user.view": true,
    "admin.user.create": true,
    "admin.user.update": true,
    "admin.user.delete": true,
    "patient.view": true,
    "patient.create": true,
    "patient.update": true
  },
  "permissions_version": "2026-04-12T08:00:00Z"
}
```

## Invalidación Manual de Caché

```php
app(PermissionService::class)->invalidateCacheForUser($userId);
app(PermissionService::class)->invalidateCacheForAllUsers();
```

## Tests

```bash
# Backend
docker compose run --rm app vendor/bin/phpunit --colors=never

# Frontend
docker compose run --rm node sh -lc "npm ci --silent && ./node_modules/.bin/vitest run"
```
