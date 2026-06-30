# Módulo Funcional — Plantillas de Informe (Report Templates)

## Propósito de Negocio

Permitir a los administradores del sistema definir y gestionar plantillas de informes médicos reutilizables. Cada plantilla define la estructura del formulario (secciones, campos, tipos) que los médicos completan al crear un informe. Las plantillas aseguran consistencia en la documentación clínica y habilitan la extracción de datos con IA.

## Actores

- **Administrador** — Crea, edita, activa/desactiva y elimina plantillas de informe.

## Funcionalidades

| Endpoint | Método | Descripción | Permiso |
|----------|--------|-------------|---------|
| `/api/admin/report-templates` | GET | Listar plantillas | `admin.reporttemplate.view` |
| `/api/admin/report-templates` | POST | Crear plantilla | `admin.reporttemplate.create` |
| `/api/admin/report-templates/{id}` | GET | Obtener plantilla | `admin.reporttemplate.view` |
| `/api/admin/report-templates/{id}` | PUT | Actualizar plantilla | `admin.reporttemplate.update` |
| `/api/admin/report-templates/{id}` | DELETE | Eliminar plantilla | `admin.reporttemplate.delete` |
| `/api/templates/active` | GET | Listar plantillas activas (público) | `report.create` |

## Criterios de Aceptación

- Las plantillas tienen un nombre y una descripción legibles.
- La estructura del formulario se define como JSON jerárquico (secciones → filas → columnas → campos).
- Cada campo tiene un tipo (texto, número, fecha, selección múltiple, etc.), etiqueta y opciones de validación.
- Los campos pueden marcarse como obligatorios u opcionales.
- Las plantillas pueden activarse o desactivarse (solo las activas están disponibles al crear informes).
- El endpoint público `/api/templates/active` devuelve solo plantillas activas.

## Reglas de Negocio

### Ciclo de vida de la plantilla

1. **Creación:** El administrador define nombre, descripción y estructura JSON de la plantilla.
2. **Activación:** Las plantillas nuevas se crean activas por defecto (`is_active = true`).
3. **Desactivación:** Se puede desactivar una plantilla; los informes existentes conservan la estructura mediante `template_structure_snapshot`.
4. **Modificación:** Se puede modificar cualquier campo de la plantilla, pero los cambios no afectan a informes ya creados (usan snapshot).
5. **Eliminación:** Solo se permite eliminar plantillas que no tengan informes asociados. Si hay informes que usan la plantilla, la eliminación se rechaza con error 409.

### Estructura del formulario

La estructura JSON sigue una jerarquía de 4 niveles:

```
sections → rows → columns → fields
```

Admite configuración de `header`, `sections` y `footer`, cada uno con su propio conjunto de secciones.

### Tipos de campo soportados

| Tipo | Descripción |
|------|-------------|
| `text` | Texto corto (máx. `max_chars`) |
| `textarea` | Texto largo |
| `number` | Número con rango (`min/max`) y decimales |
| `date` | Selección de fecha |
| `select` | Selección única con opciones |
| `multi_select` | Selección múltiple con opciones |
| `radio` | Opción única con radios |
| `checkbox` | Casilla de verificación |
| `fixed_text` | Texto fijo (no editable, soporta variables) |
| `dynamic_table` | Tabla dinámica con columnas configurables |
| `horizontal_separator` | Separador visual |

### Variables de sistema

Los campos `fixed_text` y valores por defecto pueden incluir variables que se resuelven automáticamente:

- `{paciente.nombre}`, `{paciente.edad}`, `{paciente.sexo}`, `{paciente.nro_historia}`
- `{medico.nombre}`, `{medico.matricula}`, `{medico.especialidad}`
- `{clinica.nombre}`, `{clinica.direccion}`, `{clinica.telefono}`
- `{fecha.formato_largo}`, `{fecha.actual}`

## Estructura de Datos

**GET /api/admin/report-templates — Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Historia Clínica General",
      "description": "Plantilla para la confección de la Historia Clínica General...",
      "is_active": true,
      "structure": {
        "header": { "enabled": true, "pageDisplay": "all", "sections": [...] },
        "sections": [...],
        "footer": { "enabled": true, "pageDisplay": "all", "sections": [...] }
      },
      "created_at": "2026-06-09T10:00:00Z",
      "updated_at": "2026-06-09T10:00:00Z"
    }
  ]
}
```

## Dependencias

- **Permisos:** `admin.reporttemplate.*` (4 permisos: view, create, update, delete)
- **Módulo Informes:** las plantillas proveen la estructura que los informes usan al crearse
- **Módulo Dictado y Autocompletado:** los campos con `ai_help_description` guían la extracción con IA

## Estado de Desarrollo

✅ Implementado — Completo. CRUD funcional con soft delete, estructura JSON jerárquica, 3 plantillas semilla.
