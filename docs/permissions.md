# Permisos y Roles — Resumen técnico

Resumen rápido:
- Modelo: roles ↔ permissions (pivot con `grant` = 1|-1). Usuario puede tener roles múltiples.
- Overrides: `user_permissions` permite `grant` y `origin` (`role`|`user`).
- Regla: **deny (-1) tiene prioridad** sobre allow (1).
- Cache: opcional `user_effective_permissions` y `permissions_version` para invalidación cliente.

Contrato `/api/me` (recomendado)
```json
{
  "id": 123,
  "name": "Juan",
  "email": "Juan@example.com",
  "roles": ["medico","director"],
  "permissions": { "patients.view": 1, "patients.edit": -1 },
  "permissions_version": "2026-04-12T08:00:00Z"
}
```

Auditoría
- Tabla: `audits` (type, module, actor_id, user_id, target_type, payload, meta, created_at)
- Registros inmutables; `policy.denied` para denegaciones de autorización.

Ejecutar tests (backend):
```bash
cd backend
composer install
php artisan migrate --env=testing
php artisan test
```

Ejecutar tests (frontend):
```bash
cd frontend
npm install
npm run test
```
