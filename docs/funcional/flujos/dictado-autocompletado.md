# Flujo de Dictado y Autocompletado de Informes — API

## Extracción de Datos Clínicos desde Transcripción

### Flujo Principal

1. El médico dicta el informe (proceso de transcripción de audio, externo a este endpoint).
2. El frontend obtiene el texto transcrito y el ID de la plantilla de informe seleccionada.
3. El frontend envía `POST /api/reports/{id}/extract-data` con:
   ```json
   {
     "transcript": "texto de la transcripción",
     "template_id": 7
   }
   ```
4. La API valida:
   - JWT válido (si no, 401).
   - Permiso `report.edit` (si no, 403).
   - Transcript no vacío (si no, 422).
   - Template existe y está activa (si no, 400).
   - Report con `{id}` existe (si no, 404).
5. La API construye un prompt con:
   - Instrucciones clínicas (system message).
   - Campos de la plantilla con `field`, `label`, `type`, `ai_help_description`.
   - Contexto del paciente (edad, sexo, valores de últimos 10 informes) — sin nombres ni IDs.
   - Transcript sanitizado (sin fences de markdown ni HTML).
6. Envía el prompt al LLM via HTTP con timeout de 30s.
7. El LLM responde con JSON estructurado.
8. La API valida el JSON, descarta campos extraños, calcula métricas.
9. Persiste la interacción en `llm_interactions`.
10. Responde con `200` y los datos extraídos.

### Flujo de Error — LLM Timeout

1. El LLM no responde dentro de 30 segundos.
2. `LlmTimeoutException` es lanzada.
3. La interacción se persiste con error en `response_payload`.
4. La API responde `500` con `{ "message": "Error al procesar con IA" }`.
5. El frontend muestra el error al médico y permite reintentar manualmente.

### Flujo de Error — LLM Response Inválida

1. El LLM responde con contenido que no es JSON parseable.
2. El servicio reintenta internamente 1 vez.
3. Si el reintento falla, `LlmResponseException` es lanzada.
4. La API responde `500`.
5. El frontend permite reintentar manualmente.

### Flujo de Error — Servicio de IA No Disponible

1. El proveedor LLM devuelve 503 o la conexión es rechazada.
2. `LlmUnavailableException` es lanzada.
3. La API responde `503` con `{ "message": "Servicio de IA temporalmente no disponible" }`.

### Flujo de Error — Permiso Denegado

1. Un usuario sin permiso `report.edit` intenta extraer datos.
2. Middleware `RequirePermissions` detecta la falta de permiso.
3. Responde con `403`.

### Flujo de Error — Template Inválida

1. El `template_id` no existe o está inactivo.
2. `TemplateNotFoundException` es lanzada.
3. Responde con `400` y `{ "message": "Plantilla no válida" }`.

## Diagrama de Flujo Completo

```
[Frontend]
    │
    ├── POST /reports/{id}/transcribe (endpoint externo)
    │   └── Response: { transcript, segments, ... }
    │
    └── POST /reports/{id}/extract-data
        ├── Auth check (JWT + report.edit)
        ├── Validation (transcript, template_id, report)
        ├── Patient context fetch
        ├── LLM extraction (30s timeout, 1 retry)
        ├── Interaction persistence
        └── Response: { extracted_data, confidence_scores, warnings, ... }
```

## Notas de Implementación

- El frontend **no reintenta automáticamente** en caso de 500. Muestra el error al médico y permite reintento manual.
- El tiempo de procesamiento típico del LLM es de 1–5 segundos (dependiendo del tamaño de la transcripción).
- El endpoint es síncrono — el frontend debe esperar la respuesta (máximo 30s).
