# RBAC y Auditoría — MateriaGris

Resumen del diseño e instrucciones de uso para el sistema de permisos y auditoría implementado.

## Objetivo
- Implementar control de acceso basado en roles (RBAC) con permisos finos.
- Soportar overrides por usuario y política de `deny` que prevalece sobre `allow`.
- Registrar eventos relevantes en una tabla de auditoría append-only.

## Esquema de base de datos (resumen)
- `roles` — lista de roles.
- `permissions` — permisos atómicos (clave, descripción, categoría).
- `permission_categories` — agrupación opcional de permisos.
- `role_permission` — pivote entre `roles` y `permissions` con columna `grant` (1=allow, -1=deny).
- `user_permission` — overrides directos por usuario (grant 1/-1).
- `audits` — registro append-only de eventos como `policy.denied`.

## Principios
- Deny-overrides: una negación explícita (`-1`) tiene prioridad sobre permisos concedidos por roles.
- Origen de la evaluación: el servicio de permisos computa permisos efectivos a partir de roles + overrides.
- Cache: los resultados se cachean por usuario y se invalidan cuando se actualizan roles/permissions.

## Servicios principales
- `PermissionService`: cálculo de permisos efectivos, `userHasPermission()`, `ensure()` (lanza `PermissionDeniedException` si no cumple), e invalidación de cache.
- `AuditService`: `record($actorId, $event, $metadata)` para persistir entradas en `audits`.

## Middleware
- `RequirePermissions` — usa `PermissionService::ensure()` para autorizar rutas. En caso de denegación registra un `policy.denied` en `audits` y responde `401 JSON`.

## Endpoints / Exposición al frontend
- `/api/auth/me` expone `roles` y un mapa de `permissions` (clave => true/false) para la UI.

## Cómo ejecutarlo localmente (desarrollo)
1. Migraciones y seed:

```bash
docker compose run app php artisan migrate --seed
```

2. Ejecutar tests backend:

```bash
docker compose run --rm app vendor/bin/phpunit --colors=never
```

3. Ejecutar tests frontend:

```bash
docker compose run --rm node sh -lc "npm ci --silent && ./node_modules/.bin/vitest run"
```

## Notas para reviewers del PR
- Revisar la semántica de `grant` en pivotes (`1` vs `-1`).
- Confirmar que `RequirePermissions` devuelve `401` y no `403` (decisión del equipo: usar 401 para sesiones JWT expiradas o permisos ausentes). Si quieres cambiarlo, lo ajustamos.
- Verificar que `audits` solo registre eventos relevantes y no información sensible.

## Cómo invalidar cache manualmente
- Llamar a `PermissionService::invalidateCacheForUser($userId)` desde los servicios que cambian roles/permissions.

## Ejemplos
- Comprobar permiso en backend:

```php
app(PermissionService::class)->ensure($user, 'users.view');
```

- Registrar auditoría:

```php
app(AuditService::class)->record($actor->id, 'policy.denied', ['permission'=>'users.view','target_user'=>$targetId]);
```

## Preguntas abiertas / Pendientes
- Añadir listeners para invalidación automática cuando se actualizan roles/permisos (tarea pendiente).
- Añadir integración de auditoría con herramientas externas si se requiere retención/retención de logs.

---
Esta documentación es un resumen; para detalles del código, ver las migraciones y servicios en `backend/app/`.
