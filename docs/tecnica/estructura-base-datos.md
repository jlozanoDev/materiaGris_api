# Estructura de Base de Datos — MateriaGris API

Generado a partir de las migraciones de Laravel. 22 tablas en total.

---

## 1. `users`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `name` | varchar(255) | NOT NULL |
| `email` | varchar(255) | NOT NULL, UNIQUE |
| `email_verified_at` | timestamp | NULLABLE |
| `password` | varchar(255) | NOT NULL |
| `remember_token` | varchar(100) | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete) |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\User` — `SoftDeletes`, `HasFactory`, `Notifiable`.

---

## 2. `password_reset_tokens`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `email` | varchar(255) | PK |
| `token` | varchar(255) | NOT NULL |
| `created_at` | timestamp | NULLABLE |

---

## 3. `sessions`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | varchar(255) | PK |
| `user_id` | bigint unsigned | NULLABLE, INDEX → `users.id` |
| `ip_address` | varchar(45) | NULLABLE |
| `user_agent` | text | NULLABLE |
| `payload` | longtext | NOT NULL |
| `last_activity` | int | NOT NULL, INDEX |

---

## 4-8. Tablas del sistema (Laravel internals)

- `cache` — clave-valor con expiración.
- `cache_locks` — locks para cache atómica.
- `jobs` — cola de trabajos.
- `job_batches` — lotes de jobs.
- `failed_jobs` — trabajos fallidos con UUID único.

---

## 9. `jwt_refresh_tokens`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `token_hash` | varchar(255) | NOT NULL |
| `jti` | varchar(255) | NOT NULL, INDEX |
| `ip` | varchar(255) | NULLABLE |
| `user_agent` | text | NULLABLE |
| `expires_at` | timestamp | NULLABLE |
| `revoked` | tinyint(1) | NOT NULL, DEFAULT `false` |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\RefreshToken` — `$table = 'jwt_refresh_tokens'`.

---

## 10. `addresses`

Renombrada de `direcciones`. Almacena direcciones de usuarios.

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `alias` | varchar(255) | NULLABLE |
| `street` | varchar(255) | NULLABLE |
| `number` | varchar(255) | NULLABLE |
| `floor` | varchar(255) | NULLABLE |
| `door` | varchar(255) | NULLABLE |
| `city` | varchar(255) | NULLABLE |
| `province` | varchar(255) | NULLABLE |
| `postal_code` | varchar(20) | NULLABLE |
| `country` | varchar(255) | NULLABLE |
| `lat` | decimal(10,7) | NULLABLE |
| `lng` | decimal(10,7) | NULLABLE |
| `landline_phone` | varchar(20) | NULLABLE |
| `mobile_phone` | varchar(20) | NULLABLE |
| `contact_email` | varchar(150) | NULLABLE |
| `is_primary` | tinyint(1) | NOT NULL, DEFAULT `false`, INDEX |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete) |

**Índices:** `(user_id, is_primary)`.

---

## 11. `patients`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `medical_record_number` | varchar(255) | NULLABLE, UNIQUE |
| `national_id` | varchar(255) | NULLABLE, UNIQUE |
| `first_name` | varchar(255) | NULLABLE, INDEX |
| `last_name` | varchar(255) | NULLABLE, INDEX |
| `second_last_name` | varchar(255) | NULLABLE, INDEX |
| `gender` | varchar(10) | NULLABLE, INDEX |
| `date_of_birth` | date | NULLABLE, INDEX |
| `city` | varchar(255) | NULLABLE, INDEX |
| `insurance_id` | bigint unsigned | NULLABLE, INDEX |
| `is_active` | tinyint(1) | NOT NULL, DEFAULT `true`, INDEX |
| `last_visit_at` | timestamp | NULLABLE, INDEX |
| `email` | varchar(255) | NULLABLE, INDEX |
| `phone` | varchar(255) | NULLABLE, INDEX |
| `mobile` | varchar(255) | NULLABLE, INDEX |
| `contact_name` | varchar(255) | NULLABLE |
| `contact_phone` | varchar(255) | NULLABLE, INDEX |
| `address_line1` | varchar(255) | NULLABLE |
| `address_line2` | varchar(255) | NULLABLE |
| `neighborhood` | varchar(255) | NULLABLE, INDEX |
| `postal_code` | varchar(20) | NULLABLE, INDEX |
| `state` | varchar(255) | NULLABLE, INDEX |
| `country` | varchar(255) | NULLABLE, INDEX |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\Patient` — `$appends = ['age', 'full_name']`.

---

## 12. `patient_reports`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `patient_id` | bigint unsigned | NOT NULL, FK → `patients.id` |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` |
| `template_id` | bigint unsigned | NULLABLE, FK → `report_templates.id` ON DELETE SET NULL |
| `status` | varchar(255) | NOT NULL, DEFAULT `'draft'`, INDEX. Valores: `draft`, `signed`, `closed` |
| `template_structure_snapshot` | json | NOT NULL |
| `values` | json | NOT NULL, DEFAULT `'{}'` |
| `signature_path` | varchar(255) | NULLABLE |
| `pdf_path` | varchar(255) | NULLABLE |
| `signed_at` | timestamp | NULLABLE |
| `closed_at` | timestamp | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Índices:** `patient_id`, `user_id`, `status`, `(patient_id, status)`.

**Modelo:** `App\Models\PatientReport` — `$casts: ['status' => ReportStatus::class, 'values' => 'array', 'template_structure_snapshot' => 'array']`, `$fillable: ['patient_id', 'user_id', 'template_id', 'status', 'template_structure_snapshot', 'values', 'signature_path', 'pdf_path', 'signed_at', 'closed_at']`.

**Relaciones:** `patient()` (BelongsTo Patient), `user()` (BelongsTo User), `template()` (BelongsTo ReportTemplate, withTrashed).

---

## 13. `report_templates`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `name` | varchar(255) | NOT NULL |
| `description` | text | NULLABLE |
| `is_active` | boolean | NOT NULL, DEFAULT `true` |
| `structure` | json | NOT NULL — define secciones→filas→columnas→campos del formulario |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete) |

**Modelo:** `App\Models\ReportTemplate` — `SoftDeletes`, `$casts: ['structure' => 'array', 'is_active' => 'boolean']`.

**Seed data:** 3 plantillas creadas por `ReportTemplatesSeeder`: Historia Clínica General (HCG), Informe de Alta (IA), Consentimiento Informado (CI).

---

## 14. `llm_interactions`

Registra cada interacción con servicios de IA (transcripción STT y extracción de datos).

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `patient_report_id` | bigint unsigned | NOT NULL, FK → `patient_reports.id` ON DELETE CASCADE |
| `type` | varchar(50) | NULLABLE, DEFAULT `'extraction'`. Valores: `extraction`, `stt` |
| `request_payload` | json | NOT NULL — payload enviado al LLM (metadatos, no incluye PII) |
| `response_payload` | json | NULLABLE — respuesta del LLM |
| `processing_time_ms` | integer | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\LlmInteraction` — `$casts: ['request_payload' => 'array', 'response_payload' => 'array']`, `$fillable: ['patient_report_id', 'type', 'request_payload', 'response_payload', 'processing_time_ms']`.

**Relaciones:** `patientReport()` (BelongsTo PatientReport).

---

## 15. `permission_categories`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `parent_id` | bigint unsigned | NULLABLE, FK → `permission_categories.id` ON DELETE CASCADE |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(100) | NOT NULL, UNIQUE |
| `description` | text | NULLABLE |
| `order` | int | NOT NULL, DEFAULT `0` |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\PermissionCategory` — relaciones `parent()`, `children()`, `permissions()`.

**Seed data:**

| Nombre | Slug | Orden | Padre |
|--------|------|-------|-------|
| Administración | `admin` | 1 | null |
| Usuarios | `conf-users` | 10 | admin |
| Roles y Permisos | `conf-roles` | 11 | admin |
| Seguridad Avanzada | `conf-roles-advanced` | 1 | conf-roles |
| Pacientes | `pacientes` | 20 | null |

---

## 16. `permissions`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `category_id` | bigint unsigned | NULLABLE, FK → `permission_categories.id` ON DELETE SET NULL |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(150) | NOT NULL, UNIQUE |
| `action` | varchar(150) | NULLABLE |
| `description` | text | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\Permission` — `category()`, `roles()`.

**Seed data:**

| Slug | Acción | Categoría |
|------|--------|-----------|
| `admin.user.view` | view | conf-users |
| `admin.user.create` | create | conf-users |
| `admin.user.update` | update | conf-users |
| `admin.user.delete` | delete | conf-users |
| `admin.role.view` | view | conf-roles |
| `admin.role.create` | create | conf-roles |
| `admin.role.update` | update | conf-roles |
| `admin.role.delete` | delete | conf-roles |
| `admin.permission.view` | view | conf-roles |
| `patient.view` | view | pacientes |
| `patient.create` | create | pacientes |
| `patient.update` | update | pacientes |

---

## 17. `roles`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(100) | NOT NULL, UNIQUE |
| `description` | text | NULLABLE |
| `is_system` | tinyint(1) | NOT NULL, DEFAULT `false` |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\Role` — `permissions()`, `users()`.

**Seed data:** Un rol: `admin` (Administrador, `is_system = true`).

---

## 18. `role_permissions`

Pivote: roles ↔ permissions con `grant` (1 = permitir, -1 = denegar).

- `UNIQUE (role_id, permission_id)`

---

## 19. `user_roles`

Asignación de roles a usuarios. Soporta trazabilidad.

- `UNIQUE (user_id, role_id)`
- FK: `assigned_by` → `users.id` ON DELETE SET NULL

---

## 20. `user_permissions`

Overrides directos por usuario. Traza origen (`role`|`user`).

- `UNIQUE (user_id, permission_id)`

---

## 21. `user_effective_permissions`

Tabla de materialización. Almacena el permiso efectivo calculado.

- `UNIQUE (user_id, permission_id)`
- `grant`: 1 (permitir), -1 (denegar), 0 (neutral)
- `sources`: JSON con origen de cada permiso

---

## 22. `audits`

Registro append-only de eventos del sistema.

- Índices: `type`, `module`, `actor_id`, `(target_type, target_id)`, `created_at`.

**Modelo:** `App\Models\Audit` — `$timestamps = false`.

---

## Resumen de Relaciones (FK)

| Tabla | Columna FK | Referencia | On Delete |
|-------|-----------|------------|-----------|
| `jwt_refresh_tokens` | `user_id` | `users.id` | CASCADE |
| `addresses` | `user_id` | `users.id` | CASCADE |
| `patient_reports` | `patient_id` | `patients.id` | — |
| `patient_reports` | `user_id` | `users.id` | — |
| `patient_reports` | `template_id` | `report_templates.id` | SET NULL |
| `report_templates` | (sin FK directas) | | |
| `llm_interactions` | `patient_report_id` | `patient_reports.id` | CASCADE |
| `permissions` | `category_id` | `permission_categories.id` | SET NULL |
| `permission_categories` | `parent_id` | `permission_categories.id` | CASCADE |
| `role_permissions` | `role_id` | `roles.id` | CASCADE |
| `role_permissions` | `permission_id` | `permissions.id` | CASCADE |
| `user_roles` | `user_id` | `users.id` | CASCADE |
| `user_roles` | `role_id` | `roles.id` | CASCADE |
| `user_roles` | `assigned_by` | `users.id` | SET NULL |
| `user_permissions` | `user_id` | `users.id` | CASCADE |
| `user_permissions` | `permission_id` | `permissions.id` | CASCADE |
| `user_permissions` | `applied_by` | `users.id` | SET NULL |
| `user_effective_permissions` | `user_id` | `users.id` | CASCADE |
| `user_effective_permissions` | `permission_id` | `permissions.id` | CASCADE |

## Notas

- `addresses` se creó originalmente como `direcciones` y fue renombrada.
- El rol `admin` se crea en migración y se le asignan permisos de administración y pacientes.
- No existe modelo Eloquent para `addresses`.
- `HealthStatus` es una clase DTO, no un modelo de BD.
