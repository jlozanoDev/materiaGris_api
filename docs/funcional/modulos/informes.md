# Módulo Funcional — Informes de Pacientes

## Propósito de Negocio

Proveer a los profesionales médicos de una herramienta digital para crear, editar, firmar y gestionar informes clínicos de pacientes. El sistema reemplaza el flujo de papel con un proceso digital que incluye plantillas dinámicas, transcripción por voz, extracción de datos con IA y generación de PDF.

## Actores

- **Médico** — Crea, edita, firma y cierra informes. Usa dictado por voz y autocompletado con IA.
- **Administrador** — Gestiona plantillas de informe (ver módulo de Plantillas de Informe).

## Funcionalidades

| Endpoint | Método | Descripción | Permiso |
|----------|--------|-------------|---------|
| `/api/reports` | GET | Listar informes (con filtros) | `report.view` |
| `/api/reports` | POST | Iniciar un nuevo informe | `report.create` |
| `/api/reports/{id}` | GET | Obtener detalle de un informe | `report.view` |
| `/api/reports/{id}` | PUT | Guardar borrador del informe | `report.edit` |
| `/api/reports/{id}/sign` | POST | Firmar informe | `report.sign` |
| `/api/reports/{id}/close` | POST | Cerrar informe y generar PDF | `report.close` |
| `/api/reports/{id}/pdf` | GET | Descargar PDF del informe | `report.download-pdf` |
| `/api/reports/{id}/extract-data` | POST | Extraer datos clínicos con IA | `report.edit` |
| `/api/reports/{id}/transcribe` | POST | Transcribir audio a texto | `report.edit` |

## Criterios de Aceptación

- Los médicos pueden crear informes a partir de plantillas predefinidas.
- Los informes pueden guardarse como borrador y editarse múltiples veces.
- Solo el autor puede firmar y cerrar un informe.
- El flujo de estados es: `draft` → `signed` → `closed`.
- Una vez firmado, no se puede editar el contenido.
- Una vez cerrado, solo está disponible para descarga PDF.
- La firma se captura como imagen base64 y se almacena de forma segura.
- El PDF se genera con DomPDF al cerrar el informe.
- La transcripción de audio y extracción de datos con IA son procesos síncronos.

## Reglas de Negocio

### Ciclo de vida del informe

1. **Creación (Init):** El médico selecciona paciente y plantilla. Se crea el informe en estado `draft` con una copia de la estructura de la plantilla (`template_structure_snapshot`).
2. **Edición (Draft):** El médico completa los campos del informe. Solo se puede editar en estado `draft`. Solo el autor puede editar.
3. **Firma (Sign):** El médico firma electrónicamente (imagen PNG). Requiere estado `draft`, solo el autor. Al firmar, cambia a estado `signed` y el contenido queda bloqueado.
4. **Cierre (Close):** El médico cierra el informe. Requiere estado `signed`, solo el autor. Se genera el PDF automáticamente y cambia a estado `closed`.
5. **Descarga:** Disponible para informes firmados o cerrados. Si el PDF no existe (ej. firmado pero sin cerrar), se regenera automáticamente.

### Restricciones

- Un informe no puede editarse después de firmado.
- Un informe no puede cerrarse sin estar firmado.
- Solo el autor del informe puede firmarlo, cerrarlo o editarlo.
- Si se elimina la plantilla asociada a un informe, el informe conserva la estructura mediante `template_structure_snapshot`.

## Estructura de Datos

**POST /api/reports — Crear informe (Request):**
```json
{
  "patient_id": 1,
  "template_id": 2,
  "template_structure_snapshot": { ... }
}
```

**POST /api/reports — Response (201):**
```json
{
  "id": 1,
  "patient_id": 1,
  "user_id": 1,
  "template_id": 2,
  "status": "draft",
  "template_structure_snapshot": { ... },
  "values": {},
  "signed_at": null,
  "closed_at": null,
  "created_at": "2026-06-10T10:00:00Z",
  "updated_at": "2026-06-10T10:00:00Z"
}
```

**PUT /api/reports/{id} — Guardar borrador (Request):**
```json
{
  "values": {
    "hcg_motivo_consulta": "Dolor torácico",
    "hcg_enfermedad_actual": "Paciente de 45 años...",
    "hcg_ta": "120/80",
    "hcg_fc": 72
  }
}
```

**POST /api/reports/{id}/sign — Firmar (Request):**
```json
{
  "signature": "data:image/png;base64,iVBORw0KGgo..."
}
```

## Dependencias

- **Permisos:** `report.*` (6 permisos: view, create, edit, sign, close, download-pdf)
- **Módulo Pacientes:** para asociar informes a pacientes
- **Módulo Plantillas de Informe:** para la estructura del formulario
- **Módulo Dictado y Autocompletado:** para transcripción y extracción con IA

## Estado de Desarrollo

✅ Implementado — Completo. CRUD funcional, ciclo draft→sign→closed, PDF, IA integrada.
