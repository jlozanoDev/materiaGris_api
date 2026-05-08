# Estructura de Base de Datos — MateriaGris API

Generado a partir de las migraciones de Laravel.

---

## 1. `users`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `name` | varchar(255) | NOT NULL |
| `email` | varchar(255) | NOT NULL, UNIQUE |
| `email_verified_at` | timestamp | NULLABLE |
| `password` | varchar(255) | NOT NULL |
| `remember_token` | varchar(100) | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete, añadido en migración 2026_04_22) |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\User` — usa `SoftDeletes`, `HasFactory`, `Notifiable`.

---

## 2. `password_reset_tokens`

| Columna | Tipo | Restricciones |
|---|---|---|
| `email` | varchar(255) | PRIMARY KEY |
| `token` | varchar(255) | NOT NULL |
| `created_at` | timestamp | NULLABLE |

---

## 3. `sessions`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | varchar(255) | PRIMARY KEY |
| `user_id` | bigint unsigned | NULLABLE, INDEX → `users.id` (FK implícita) |
| `ip_address` | varchar(45) | NULLABLE |
| `user_agent` | text | NULLABLE |
| `payload` | longtext | NOT NULL |
| `last_activity` | int | NOT NULL, INDEX |

---

## 4. `cache`

| Columna | Tipo | Restricciones |
|---|---|---|
| `key` | varchar(255) | PRIMARY KEY |
| `value` | mediumtext | NOT NULL |
| `expiration` | int | NOT NULL, INDEX |

---

## 5. `cache_locks`

| Columna | Tipo | Restricciones |
|---|---|---|
| `key` | varchar(255) | PRIMARY KEY |
| `owner` | varchar(255) | NOT NULL |
| `expiration` | int | NOT NULL, INDEX |

---

## 6. `jobs`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `queue` | varchar(255) | NOT NULL, INDEX |
| `payload` | longtext | NOT NULL |
| `attempts` | unsigned tinyint | NOT NULL |
| `reserved_at` | unsigned int | NULLABLE |
| `available_at` | unsigned int | NOT NULL |
| `created_at` | unsigned int | NOT NULL |

---

## 7. `job_batches`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | varchar(255) | PRIMARY KEY |
| `name` | varchar(255) | NOT NULL |
| `total_jobs` | int | NOT NULL |
| `pending_jobs` | int | NOT NULL |
| `failed_jobs` | int | NOT NULL |
| `failed_job_ids` | longtext | NOT NULL |
| `options` | mediumtext | NULLABLE |
| `cancelled_at` | int | NULLABLE |
| `created_at` | int | NOT NULL |
| `finished_at` | int | NULLABLE |

---

## 8. `failed_jobs`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `uuid` | varchar(255) | NOT NULL, UNIQUE |
| `connection` | text | NOT NULL |
| `queue` | text | NOT NULL |
| `payload` | longtext | NOT NULL |
| `exception` | longtext | NOT NULL |
| `failed_at` | timestamp | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

---

## 9. `jwt_refresh_tokens`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
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

## 10. `addresses` (originalmente `direcciones`, renombrada en migración 2026_04_13)

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `alias` | varchar(255) | NULLABLE |
| `street` | varchar(255) | NULLABLE (antes `calle`) |
| `number` | varchar(255) | NULLABLE (antes `numero`) |
| `floor` | varchar(255) | NULLABLE (antes `piso`) |
| `door` | varchar(255) | NULLABLE (antes `puerta`) |
| `city` | varchar(255) | NULLABLE (antes `ciudad`) |
| `province` | varchar(255) | NULLABLE (antes `provincia`) |
| `postal_code` | varchar(20) | NULLABLE (antes `codigo_postal`) |
| `country` | varchar(255) | NULLABLE (antes `pais`) |
| `lat` | decimal(10,7) | NULLABLE |
| `lng` | decimal(10,7) | NULLABLE |
| `landline_phone` | varchar(20) | NULLABLE (antes `telefono_fijo`) |
| `mobile_phone` | varchar(20) | NULLABLE (antes `movil_contacto`) |
| `contact_email` | varchar(150) | NULLABLE (antes `email_contacto`) |
| `is_primary` | tinyint(1) | NOT NULL, DEFAULT `false`, INDEX (antes `es_principal`) |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete) |

**Índices adicionales:**
- `INDEX (user_id, is_primary)`

---

## 11. `patients`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
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
| `email` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `phone` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `mobile` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `contact_name` | varchar(255) | NULLABLE (añadido en 2026_04_04) |
| `contact_phone` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `address_line1` | varchar(255) | NULLABLE (añadido en 2026_04_04) |
| `address_line2` | varchar(255) | NULLABLE (añadido en 2026_04_04) |
| `neighborhood` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `postal_code` | varchar(20) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `state` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `country` | varchar(255) | NULLABLE, INDEX (añadido en 2026_04_04) |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\Patient` — incluye `$appends = ['age', 'full_name']`.

---

## 12. `permission_categories`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `parent_id` | bigint unsigned | NULLABLE, FK → `permission_categories.id` ON DELETE CASCADE (añadido en 2026_04_23) |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(100) | NOT NULL, UNIQUE |
| `description` | text | NULLABLE |
| `order` | int | NOT NULL, DEFAULT `0` |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\PermissionCategory` — relaciones `parent()`, `children()`, `permissions()`.

### Datos sembrados por migraciones:

| Nombre | Slug | Orden | Padre |
|---|---|---|---|
| Administración | `admin` | 1 | `null` |
| Usuarios | `conf-users` | 10 | `admin` |
| Roles y Permisos | `conf-roles` | 11 | `admin` |
| Seguridad Avanzada | `conf-roles-advanced` | 1 | `conf-roles` |
| Pacientes | `pacientes` | 20 | `null` |

---

## 13. `permissions`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `category_id` | bigint unsigned | NULLABLE, FK → `permission_categories.id` ON DELETE SET NULL |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(150) | NOT NULL, UNIQUE |
| `action` | varchar(150) | NULLABLE |
| `description` | text | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Índices adicionales:**
- `INDEX (slug)`
- `INDEX (category_id)`

**Modelo:** `App\Models\Permission` — relación `category()`, `roles()`.

### Datos sembrados por migraciones:

| Nombre | Slug | Acción | Categoría |
|---|---|---|---|
| Ver Usuarios | `admin.user.view` | `view` | conf-users |
| Crear Usuarios | `admin.user.create` | `create` | conf-users |
| Modificar Usuarios | `admin.user.update` | `update` | conf-users |
| Eliminar Usuarios | `admin.user.delete` | `delete` | conf-users |
| Ver Roles | `admin.role.view` | `view` | conf-roles |
| Crear Roles | `admin.role.create` | `create` | conf-roles |
| Modificar Roles | `admin.role.update` | `update` | conf-roles |
| Eliminar Roles | `admin.role.delete` | `delete` | conf-roles |
| Ver Permisos | `admin.permission.view` | `view` | conf-roles |
| Ver Pacientes | `patient.view` | `view` | pacientes |
| Crear Pacientes | `patient.create` | `create` | pacientes |
| Modificar Pacientes | `patient.update` | `update` | pacientes |

---

## 14. `roles`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `name` | varchar(100) | NOT NULL |
| `slug` | varchar(100) | NOT NULL, UNIQUE |
| `description` | text | NULLABLE |
| `is_system` | tinyint(1) | NOT NULL, DEFAULT `false` |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Modelo:** `App\Models\Role` — relaciones `permissions()`, `users()`.

### Datos sembrados por migraciones:

| Nombre | Slug | Sistema |
|---|---|---|
| Administrador | `admin` | `true` |

---

## 15. `role_permissions`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `role_id` | bigint unsigned | NOT NULL, FK → `roles.id` ON DELETE CASCADE |
| `permission_id` | bigint unsigned | NOT NULL, FK → `permissions.id` ON DELETE CASCADE |
| `grant` | tinyint | NOT NULL, DEFAULT `1` (1 = permitir, -1 = denegar) |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Restricciones adicionales:**
- `UNIQUE (role_id, permission_id)`

---

## 16. `user_roles`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `role_id` | bigint unsigned | NOT NULL, FK → `roles.id` ON DELETE CASCADE |
| `assigned_by` | bigint unsigned | NULLABLE, FK → `users.id` ON DELETE SET NULL |
| `assigned_at` | timestamp | NULLABLE |
| `revoked_at` | timestamp | NULLABLE |
| `meta` | json | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Restricciones adicionales:**
- `UNIQUE (user_id, role_id)`
- `INDEX (user_id)`
- `INDEX (role_id)`

---

## 17. `user_permissions`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `permission_id` | bigint unsigned | NOT NULL, FK → `permissions.id` ON DELETE CASCADE |
| `grant` | tinyint | NOT NULL, DEFAULT `1` (1 = permitir, -1 = denegar) |
| `origin` | varchar(50) | NOT NULL, DEFAULT `'user'` (`'role'` o `'user'`) |
| `origin_id` | bigint unsigned | NULLABLE (id del rol si `origin='role'`) |
| `applied_by` | bigint unsigned | NULLABLE, FK → `users.id` ON DELETE SET NULL |
| `reason` | text | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Restricciones adicionales:**
- `UNIQUE (user_id, permission_id)`
- `INDEX (user_id)`
- `INDEX (permission_id)`

---

## 18. `user_effective_permissions`

Tabla de materialización — almacena el permiso efectivo calculado para cada usuario.

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE |
| `permission_id` | bigint unsigned | NOT NULL, FK → `permissions.id` ON DELETE CASCADE |
| `grant` | tinyint | NOT NULL, DEFAULT `0` (1 = permitir, -1 = denegar, 0 = neutral) |
| `sources` | json | NULLABLE |
| `calculated_at` | timestamp | NULLABLE |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |

**Restricciones adicionales:**
- `UNIQUE (user_id, permission_id)`
- `INDEX (user_id)`

---

## 19. `audits`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT |
| `type` | varchar(150) | NOT NULL |
| `module` | varchar(100) | NULLABLE |
| `actor_id` | bigint unsigned | NULLABLE |
| `actor_type` | varchar(100) | NOT NULL, DEFAULT `'User'` |
| `user_id` | bigint unsigned | NULLABLE |
| `target_type` | varchar(100) | NULLABLE |
| `target_id` | bigint unsigned | NULLABLE |
| `ip_address` | varchar(45) | NULLABLE |
| `user_agent` | text | NULLABLE |
| `payload` | json | NULLABLE |
| `meta` | json | NULLABLE |
| `trace_id` | varchar(255) | NULLABLE |
| `created_at` | timestamp | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

**Índices adicionales:**
- `INDEX (type)`
- `INDEX (module)`
- `INDEX (actor_id)`
- `INDEX (target_type, target_id)`
- `INDEX (created_at)`

**Modelo:** `App\Models\Audit` — `$timestamps = false`.

---

## Resumen de relaciones (FK)

| Tabla | Columna FK | Referencia | On Delete |
|---|---|---|---|
| `sessions` | `user_id` | `users.id` | — (index only) |
| `jwt_refresh_tokens` | `user_id` | `users.id` | CASCADE |
| `addresses` | `user_id` | `users.id` | CASCADE |
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

---

## Notas

- La tabla `addresses` se creó originalmente como `direcciones` y fue renombrada junto con todas sus columnas en la migración `2026_04_13_000000_rename_direcciones_to_addresses_table.php`.
- El rol `admin` (Administrador) se crea en `2026_04_17_000000_add_admin_role_with_user_permissions.php` y se le asignan permisos de gestión de usuarios, roles y pacientes a través de migraciones posteriores.
- No existe un modelo Eloquent para `addresses`; solo se usa a través de `DB` queries o relación directa desde `User`.
- `HealthStatus` no es un modelo de base de datos, solo una clase DTO.
