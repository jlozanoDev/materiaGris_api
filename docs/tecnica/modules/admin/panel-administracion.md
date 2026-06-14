# Panel de Administración — Vista General Técnica

## Rutas del Módulo Admin

Todas las rutas están bajo el prefijo `/api/admin` y protegidas por `auth.jwt`.

| Submódulo | Endpoints |
|-----------|-----------|
| Usuarios | 5 (CRUD) |
| Roles | 5 (CRUD) |
| Permisos | 1 (read-only) |
| Variables del Sistema | 1 (read-only) |
| **Total** | **12** |

## Middleware Aplicado

- `auth.jwt` — grupo completo.
- `require_permissions:{permiso}` — por endpoint individual.

## Acceso a Submódulos

Ir a:
- [`usuarios-crud.md`](./usuarios-crud.md) — CRUD de usuarios del sistema
- [`roles-crud.md`](./roles-crud.md) — CRUD de roles y asignación de permisos
- [`permisos-crud.md`](./permisos-crud.md) — Catálogo de permisos del sistema

## Estado General

✅ Implementado — Los 12 endpoints están operativos.

## Pendientes del Módulo Admin (global)

| Pendiente | Prioridad |
|-----------|-----------|
| Paginación en listados de usuarios y roles | Alta |
| Tests feature para todos los endpoints de admin | Alta |
| Filtros de búsqueda en listado de usuarios | Media |
| Confirmación de eliminación de roles de sistema | Media |
| Historial de cambios (auditoría) | Baja |
