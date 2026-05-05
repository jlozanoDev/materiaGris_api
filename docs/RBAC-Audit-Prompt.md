## Plan: Implementación RBAC y Auditoría

TL;DR
Implementar un sistema centralizado de roles/permissions y auditoría reutilizable según el MD `.github/funcionales/permisos_roles.md`. Incluye: migraciones, modelos, servicios (PermissionService, AuditService), middleware de autorización (`RequirePermissions` / `PermissionChecker`), evento/listeners para auditoría, actualización del endpoint `/api/me`, cambios en frontend (Pinia store `acl`, helpers `hasPermission`, directiva `v-has-permission`, route guards) y tests (unit, integration, E2E). El plan contempla dos rutas de implementación (Opción A: integrar `spatie/laravel-permission` para acelerar; Opción B: implementación custom para denegaciones y auditoría fina).

**Steps**
1. Alineación y decisión (bloqueante)
   - Confirmar Opción A (usar `spatie/laravel-permission`) o Opción B (implementación custom que soporte deny overrides, `origin` en asignaciones y auditoría completa).
   - Confirmar política sobre incluir permisos en JWT (claim `scopes`) o siempre cargar desde DB y exponer `permissions_version` en `/api/me`.

2. Phase 1 — Esquema de BD y migraciones (*depends on Step 1*)
   - Crear migraciones (ordenadas):
     - `create_permission_categories_table`
     - `create_permissions_table`
     - `create_roles_table`
     - `create_role_permissions_table` (pivot, con `grant` 1/-1)
     - `create_user_roles_table` (pivot con `assigned_by`, `assigned_at`, `revoked_at`, `meta`)
     - `create_user_permissions_table` (overrides + origin fields: `grant`, `origin` ('role'|'user'), `origin_id`)
     - `create_user_effective_permissions_table` (opcional cache)
     - `create_audits_table` (según especificación en MD)
   - Índices recomendados: `audits(type,module,created_at)`, `user_permissions(user_id,permission_id)`, `role_permissions(role_id,permission_id)`.

3. Phase 2 — Modelos, Repositorios y Factories (*parallelizable dentro de backend*)
   - Modelos: `Role`, `Permission`, `Audit`.
   - Modificar `User` para añadir relaciones: `roles()`, `permissions()` (overrides), `effectivePermissions()`.
   - Repositorios: `RoleRepository`, `PermissionRepository`, `UserRepository` (metodos para cargar permisos eficientemente).
   - Factories/Seeders: crear roles y permisos base (ej. `admin`, `medico`, `viewer`) y seeders iniciales.

4. Phase 3 — Servicios y lógica de autorización (*depends on 2,3*)
   - `PermissionService`:
     - Método `getEffectivePermissions(User $user): array` (combina role permissions + user overrides, aplica regla "más restrictiva").
     - Método `rebuildEffectivePermissions(User $user)` que actualiza `user_effective_permissions` y marca `users.permissions_version`.
     - Helpers: `userHasPermission(User $user, string $permission): bool`, `ensure(User $user, array $perms, string $mode='any')` (lanza `PermissionDeniedException`).
   - Implementar origen (`origin`/`origin_id`) en `user_permissions` para diferenciar permisos aplicados por roles vs overrides manuales.
   - Automatizar aplicación de permisos de roles en la ficha del usuario: al asignar un rol, crear `user_permissions` con `origin='role'` y `origin_id=role_id`; al revocar, eliminar sólo los `user_permissions` con `origin='role' && origin_id=role_id`.
   - Cache/Invalidación: usar Redis para cache temporal de `effectivePermissions`, invalidar al cambiar roles/permissions o cuando `users.permissions_version` cambie.

5. Phase 4 — Auditoría (audits)
   - Modelo `Audit` y `AuditService::record(string $type, $actor, $target=null, array $payload=[], array $meta=[])`.
   - Eventos: `audit.logged` emitted on record.
   - Listeners: `LogRolePermissionChanges`, `LogPermissionDenial` (captura `PermissionDeniedException` y escribe `policy.denied`).
   - Política de inmutabilidad: nunca actualizar 'audits' (solo append). Si payload muy grande: guardar hash + referencia externa (S3) y en `payload` almacenar meta.

6. Phase 5 — Middleware, Exceptions y Provider (*depends on 3,4*)
   - Crear `PermissionDeniedException` (mapear a HTTP 401 en `Handler`).
   - Middleware `RequirePermissions` (parámetros `permissions` y `mode=any|all`) que use `PermissionService::ensure(...)`.
   - Registrar middleware en `app/Http/Kernel.php` como `require_permissions`.
   - `AuthServiceProvider`: bind `PermissionService` y registrar gates/policies si procede.

7. Phase 6 — Endpoints/API y sincronización con JWT (*depends on 3,5*)
   - Modificar `MeAction` / `MeCommand` / `GetUserRepository` para incluir en la respuesta:
     - `roles` (array de slugs/nombres),
     - `permissions` (map `"slug": grant`),
     - `permissions_version` (timestamp/uuid)
   - Nuevo endpoint admin para CRUD roles/permissions y asignaciones (ej. `/admin/roles`, `/admin/permissions`, `/admin/users/{id}/roles`).
   - Opcional: incluir permisos en JWT `scopes` (si se elige), con estrategia de revocación.

8. Phase 7 — Frontend (Vue) (*depends on 6*)
   - API: `ApiUserRepository.me()` adaptar a nuevo contrato.
   - Store: `auth` o nuevo `acl` Pinia store con `user`, `roles`, `permissions` (map), `permissions_version` y métodos `hasPermission`, `hasPermissions`, `hasRole`.
   - Helpers: `hasPermission(slug, mode='any'|'all')` y `hasPermissions(array, mode)`.
   - Directive: `v-has-permission` (alias `v-can`) para templates.
   - Router guards: soportar `meta.permissions` / `meta.roles` y validar antes de navigation.
   - UX: adaptaciones en `AppSidebar.vue` y componentes para ocultar acciones; proteger botones y menús.
   - Cache/Invalidación: usar `permissions_version` para forzar `GET /api/me` si cambia, opcional WS/SSE para notificar cambios.

9. Phase 8 — Tests y QA (*parallel with implementation phases but must be added as code is written*)
   - Backend unit tests: `PermissionServiceTest`, `RequirePermissionsMiddlewareTest`, `AuditServiceTest`, API integration tests asserting 401 on acceso no autorizado.
   - Frontend unit tests: `acl.store.spec.js`, `v-has-permission.spec.js`, component tests (AppSidebar).
   - E2E tests: flujos de login, navegación y verificación de 401 (cypress/playwright) o E2E con Vitest+Playwright.
   - CI: añadir jobs para `phpunit` y `vitest`.

10. Phase 9 — Operaciones y mantenimiento
    - Índices y mantenimiento de `audits` (archiving/retention policy).
    - Monitorización del tamaño de tablas y cache hit/miss.
    - Migration rollout: ejecutar en staging, verificar y luego prod.

11. Phase 10 — Documentación y handoff
    - Actualizar `.github/funcionales/permisos_roles.md` (ya actualizado).
    - Documentar endpoints, contrato `/api/me`, directiva `v-has-permission`, y ejemplos de uso.

**Archivos relevantes (a crear/modificar)**
- backend/database/migrations/create_permission_categories_table.php — campos: id,name,slug,description,order,timestamps
- backend/database/migrations/create_permissions_table.php — category_id, name, slug, action, description,timestamps
- backend/database/migrations/create_roles_table.php — id,name,slug,description,is_system,timestamps
- backend/database/migrations/create_role_permissions_table.php — role_id,permission_id,grant (1|-1),timestamps
- backend/database/migrations/create_user_roles_table.php — user_id,role_id,assigned_by,assigned_at,revoked_at,meta
- backend/database/migrations/create_user_permissions_table.php — user_id,permission_id,grant,origin,origin_id,applied_by,reason,timestamps
- backend/database/migrations/create_user_effective_permissions_table.php (opcional cache)
- backend/database/migrations/create_audits_table.php — según MD (type,module,actor_id,actor_type,user_id,target_type,target_id,ip_address,user_agent,payload,meta,trace_id,created_at)
- backend/app/Models/Role.php — relaciones `permissions()`, `users()`
- backend/app/Models/Permission.php — relaciones `roles()`
- backend/app/Models/Audit.php — modelo read-only
- backend/app/Models/User.php — añadir `roles()`, `userPermissions()`, `effectivePermissions()`
- backend/app/Services/PermissionService.php — calculo e invalidación
- backend/app/Services/AuditService.php — `record()`
- backend/app/Http/Middleware/RequirePermissions.php — middleware
- backend/app/Exceptions/PermissionDeniedException.php — excepción
- backend/app/Providers/AuthServiceProvider.php — bindings
- backend/app/Http/Actions/Auth/MeAction.php — incluir `roles`, `permissions`, `permissions_version`
- backend/app/Repositories/* (RoleRepository, PermissionRepository)
- backend/app/Listeners/LogRolePermissionChanges.php
- backend/tests/Unit/PermissionServiceTest.php, AuditServiceTest.php, Middleware tests
- frontend/src/core/store/auth.js or frontend/src/core/store/acl.js — añadir `permissions` y helpers
- frontend/src/shared/directives/v-has-permission.js — directiva
- frontend/src/core/router/index.js — route guards
- frontend/src/modules/auth/infrastructure/ApiUserRepository.js — mapear respuesta `me()`
- frontend/tests/unit/acl.spec.js, directives.spec.js, AppSidebar.spec.js

**Verificación (comandos sugeridos)**
- Backend: ejecutar migraciones y tests

```bash
cd backend
composer install
php artisan migrate --path=/database/migrations
php artisan test
# o
./vendor/bin/phpunit
```

- Frontend: instalar y ejecutar tests

```bash
cd frontend
npm install
npm run test
# Ejecutar Vite dev para pruebas manuales
npm run dev
```

- Prueba manual rápida
  - Crear un usuario admin seeded con roles/permissions.
  - Login vía UI o API, comprobar `GET /api/me` devuelve `permissions` y `permissions_version`.
  - Intentar llamar a endpoint protegido sin permiso -> debe retornar 401 y generar `audit` con `policy.denied`.

**Decisiones (requieren tu confirmación)**
- Opción A (rápida): integrar `spatie/laravel-permission` y extender para auditoría. Pros: menos código, package probado. Contras: adaptar para `deny` overrides y auditoría fina.
- Opción B (recomendada para MD): implementación custom que soporte `grant` (+1/-1), `origin` en `user_permissions`, auditoría y cache control. Pros: control total; Contras: mayor tiempo de desarrollo.
- ¿Incluir permisos en JWT `scopes`? (si sí, planificar invalidación/rotation de JWT o usar short-lived tokens).

**Estimación orientativa**
- Opción A: 2–4 días (migraciones, wiring backend, MeAction, simple frontend changes, tests básicos).
- Opción B: 6–12 días (migraciones, servicio de permisos, cache, auditoría, listeners, tests completos, frontend).

**Siguientes pasos propuestos**
1. Confirma Opción A o B y si quieres incluir `scopes` en JWT.
2. Aprobar alcance y entorno de despliegue (staging/prod) y confirmar si trabajamos en una feature branch `feature/rbac-audits`.
3. Aprobación para implementar: generar PR con cambios backend y frontend por fases (migraciones -> services -> middleware -> endpoint -> frontend -> tests).

---
Plan guardado en `/memories/session/plan.md`. Responde si prefieres Opción A o B y si autorizas que empiece a generar el código (yo sólo prepararé el plan y los cambios si confirmas).
