# Módulo de Plantillas de Informe — Documentación Técnica

> **Módulo**: Admin — Report Templates (gestión de plantillas de informe)
> **Documentación funcional**: [`docs/funcional/modulos/plantillas-informes.md`](../../../funcional/modulos/plantillas-informes.md)
> **Flujo de API**: [`docs/funcional/flujos/gestion-informes.md`](../../../funcional/flujos/gestion-informes.md)

## Rutas

### Admin CRUD (protegidas por `auth.jwt`)

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| GET | `/api/admin/report-templates` | `auth.jwt`, `require_permissions:admin.reporttemplate.view` | `admin.reporttemplate.view` | `ListReportTemplatesAction` |
| POST | `/api/admin/report-templates` | `auth.jwt`, `require_permissions:admin.reporttemplate.create` | `admin.reporttemplate.create` | `CreateReportTemplateAction` |
| GET | `/api/admin/report-templates/{id}` | `auth.jwt`, `require_permissions:admin.reporttemplate.view` | `admin.reporttemplate.view` | `GetReportTemplateAction` |
| PUT | `/api/admin/report-templates/{id}` | `auth.jwt`, `require_permissions:admin.reporttemplate.update` | `admin.reporttemplate.update` | `UpdateReportTemplateAction` |
| DELETE | `/api/admin/report-templates/{id}` | `auth.jwt`, `require_permissions:admin.reporttemplate.delete` | `admin.reporttemplate.delete` | `DeleteReportTemplateAction` |

### Público Reports (protegidas por `auth.jwt`)

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| GET | `/api/templates/active` | `auth.jwt`, `require_permissions:report.create` | `report.create` | `GetActiveTemplatesAction` |

## Actions

### `ListReportTemplatesAction`
- Invoca `ListReportTemplatesCommand` con filtros opcionales: `is_active`, `q` (búsqueda por nombre), `per_page`.
- Retorna paginación estándar.
- **Response 200:** `{ data: ReportTemplate[], meta: { current_page, last_page, per_page, total } }`.

### `CreateReportTemplateAction`
- Invoca `CreateReportTemplateCommand` con datos validados por `CreateReportTemplateRequest`.
- **Response 201:** Retorna la plantilla creada.
- **422:** Si los datos de validación fallan.

### `GetReportTemplateAction`
- Invoca `GetReportTemplateCommand` con el ID de la plantilla.
- **Response 200:** Retorna la plantilla.
- **404:** Si el ID no existe.

### `UpdateReportTemplateAction`
- Invoca `UpdateReportTemplateCommand` con datos validados por `UpdateReportTemplateRequest`.
- **Response 200:** Retorna la plantilla actualizada.
- **404:** Si el ID no existe.

### `DeleteReportTemplateAction`
- Invoca `DeleteReportTemplateCommand` con el ID.
- **Response 204:** Sin contenido (eliminación exitosa).
- **404:** Si el ID no existe.
- **409:** Si la plantilla tiene informes de pacientes asociados (conflicto).

### `GetActiveTemplatesAction`
- Invoca `GetActiveTemplatesCommand` — retorna solo plantillas con `is_active = true`.
- No requiere permisos de administración, solo autenticación JWT.
- **Response 200:** `{ data: ReportTemplate[] }` — lista de plantillas activas (sin paginación).

## Commands (Use Cases)

| Command | Método `execute` | Lógica clave |
|---------|------------------|--------------|
| `ListReportTemplatesCommand` | `(array $filters): LengthAwarePaginator` | Verifica `admin.reporttemplate.view`, delega al repositorio |
| `CreateReportTemplateCommand` | `(array $data): ReportTemplate` | Verifica `admin.reporttemplate.create`, crea con `name`, `description`, `structure`, `is_active` |
| `GetReportTemplateCommand` | `(int $id): ?ReportTemplate` | Verifica `admin.reporttemplate.view`, busca por ID (incluye soft deleted) |
| `UpdateReportTemplateCommand` | `(int $id, array $data): ReportTemplate` | Verifica `admin.reporttemplate.update`, actualiza campos permitidos |
| `DeleteReportTemplateCommand` | `(int $id): void` | Verifica `admin.reporttemplate.delete`, valida que no tenga informes asociados antes de eliminar |
| `GetActiveTemplatesCommand` | `(): Collection` | Sin verificación de permiso específico (solo JWT), retorna `where('is_active', true)->get()` |

## Repositories

Las operaciones CRUD de ReportTemplate utilizan Eloquent directamente a través del modelo, sin repositorio separado.

## Modelos

### `ReportTemplate` — Tabla: `report_templates`

| Columna | Tipo | Restricciones |
|---------|------|---------------|
| `id` | bigint unsigned | PK, AUTO_INCREMENT |
| `name` | varchar(255) | NOT NULL — nombre legible de la plantilla |
| `description` | text | NULLABLE — descripción del propósito de la plantilla |
| `is_active` | boolean | DEFAULT `true` — indica si está disponible para uso |
| `structure` | json | NOT NULL — estructura jerárquica del formulario |
| `created_at` | timestamp | NULLABLE |
| `updated_at` | timestamp | NULLABLE |
| `deleted_at` | timestamp | NULLABLE (soft delete) |

**Modelo:** `App\Models\ReportTemplate` — `SoftDeletes`, `$casts: ['structure' => 'array', 'is_active' => 'boolean']`.

## Estructura JSON de `structure`

El campo `structure` contiene la definición completa del formulario de la plantilla. Sigue una jerarquía de 4 niveles:

```
structure (object)
├── header (object) — encabezado global
│   └── sections[] — secciones del encabezado
│       └── rows[] — filas de la sección
│           └── columns[] — columnas de la fila
│               └── fields[] — campos del formulario
├── sections[] — secciones del cuerpo principal
│   └── rows[] → columns[] → fields[]
└── footer (object) — pie global
    └── sections[] → rows[] → columns[] → fields[]
```

### Campos del formulario (`field`)

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `id` | string (UUID) | Identificador único del campo |
| `key` | string | Clave única del campo (ej. `hcg_motivo_consulta`) |
| `type` | string | Tipo de campo: `text`, `textarea`, `number`, `date`, `select`, `multi_select`, `radio`, `checkbox`, `fixed_text`, `dynamic_table`, `horizontal_separator` |
| `label` | string | Etiqueta visible del campo |
| `required` | boolean | Si el campo es obligatorio |
| `showLabel` | boolean | Si se muestra la etiqueta en el formulario |
| `placeholder` | string | Texto de placeholder (opcional) |
| `default_value` | mixed | Valor por defecto (opcional, puede incluir variables `{medico.nombre}`) |
| `max_chars` | integer | Longitud máxima para campos `text` |
| `min` / `max` | number | Rango para campos `number` |
| `decimals` | integer | Decimales para campos `number` |
| `options` | array | Opciones para `select`, `multi_select`, `radio` — cada opción: `{ label, value }` |
| `columns` | array | Columnas para `dynamic_table` — cada columna: `{ key, label, type, required, options }` |
| `text_content` | string | Contenido fijo para campos `fixed_text` (soporta variables `{...}`) |
| `styling_options` | object | Opciones de estilo: `{ bold, size }` |
| `ai_help_description` | string | Descripción semántica para el LLM durante extracción de datos |

### Variables de sistema

Los campos `fixed_text` y `default_value` pueden incluir variables delimitadas por `{...}` que se resuelven en tiempo de renderizado:

| Variable | Resuelve |
|----------|----------|
| `{clinica.nombre}` | Nombre de la clínica |
| `{clinica.direccion}` | Dirección de la clínica |
| `{clinica.telefono}` | Teléfono de la clínica |
| `{paciente.nombre}` | Nombre completo del paciente |
| `{paciente.nro_historia}` | Número de historia clínica |
| `{paciente.edad}` | Edad del paciente |
| `{paciente.sexo}` | Sexo del paciente |
| `{medico.nombre}` | Nombre del médico tratante |
| `{medico.matricula}` | Matrícula del médico |
| `{medico.especialidad}` | Especialidad del médico |
| `{fecha.formato_largo}` | Fecha actual en formato largo |
| `{fecha.actual}` | Fecha actual |

## Plantillas por Defecto (Seed)

Tres plantillas se crean mediante `ReportTemplatesSeeder`:

| Nombre | Slug de propósito | Descripción |
|--------|-------------------|-------------|
| Historia Clínica General | hcg | Plantilla completa con anamnesis, examen físico, diagnóstico y plan terapéutico |
| Informe de Alta | ia | Plantilla con datos de ingreso, evolución, diagnóstico de egreso y recomendaciones |
| Consentimiento Informado | ci | Plantilla con secciones de procedimiento, riesgos y declaración del paciente |

## Códigos de Error

| Código | Cuándo ocurre |
|--------|---------------|
| 403 | Sin permiso requerido |
| 404 | Plantilla no encontrada |
| 409 | Conflicto al eliminar — plantilla tiene informes asociados |
| 422 | Validación fallida en creación/actualización |
| 500 | Error interno del servidor |

## Estado de Desarrollo

✅ Completo — 6 endpoints implementados, CRUD completo con soft delete, estructura JSON jerárquica documentada, 3 plantillas semilla, endpoint público de plantillas activas.
