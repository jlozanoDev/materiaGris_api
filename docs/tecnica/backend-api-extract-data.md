# Especificación Backend: POST /reports/{id}/extract-data

> **Para**: Equipo Backend (Laravel)  
> **Módulo**: Dictado y Autocompletado de Informes con IA  
> **Fecha**: 2025-06-22  

---

## 1. Resumen

Recibe una transcripción de audio (texto plano), la estructura de una plantilla de informe médico, y extrae los datos clínicos relevantes usando un LLM. Devuelve un JSON estructurado cuyas claves corresponden a los campos de la plantilla.

---

## 2. Endpoint

| Campo | Valor |
|-------|-------|
| **Método** | `POST` |
| **URL** | `/api/reports/{id}/extract-data` |
| **Content-Type** | `application/json` |
| **Auth** | Bearer token (mismo sistema que el resto de la API) |

El `{id}` es el ID del informe (`PatientReport`) al que se asociarán los datos extraídos.

---

## 3. Request Body

```json
{
  "transcript": "string  (requerido)",
  "template_id": "integer  (requerido)"
}
```

### Campos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `transcript` | `string` | Sí | Texto completo de la transcripción. Puede ser largo (miles de caracteres). |
| `template_id` | `integer` | Sí | ID de la plantilla (`ReportTemplate`) cuya estructura de campos se usará para extraer datos. |

### Ejemplo Request

```json
POST /api/reports/42/extract-data
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6...
Content-Type: application/json

{
  "transcript": "Paciente: Juan Pérez, 45 años. Consulta por dolor torácico. Refiere dolor opresivo de 3 horas de evolución, irradiado a brazo izquierdo. No antecedentes de cardiopatía. Presión arterial 140/90. Frecuencia cardíaca 88 lpm. Diagnóstico: Síndrome coronario agudo. Tratamiento: AAS 100mg, Clopidogrel 75mg, Nitroglicerina sublingual.",
  "template_id": 7
}
```

---

## 4. Response Body (200 OK)

```json
{
  "extracted_data": {
    "motivo_consulta": "Dolor torácico",
    "antecedentes": "No antecedentes de cardiopatía",
    "examen_fisico": "PA 140/90, FC 88 lpm",
    "diagnostico": "Síndrome coronario agudo",
    "tratamiento": "AAS 100mg, Clopidogrel 75mg, Nitroglicerina sublingual"
  },
  "confidence_scores": {
    "motivo_consulta": 0.95,
    "antecedentes": 0.88,
    "examen_fisico": 0.92,
    "diagnostico": 0.97,
    "tratamiento": 0.94
  },
  "warnings": [
    "No se encontró información sobre 'alergias' en la transcripción"
  ],
  "processing_time_ms": 1250
}
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `extracted_data` | `object` | Clave-valor donde cada clave es un `field.key` de la plantilla y el valor es el dato extraído por el LLM. Valores pueden ser `string`, `number`, `boolean`, `null`, o arrays. |
| `confidence_scores` | `object` | Misma estructura que `extracted_data` pero con valores `float` entre 0.0 y 1.0. Indica la confianza del LLM para cada campo. |
| `warnings` | `string[]` | Array de mensajes informativos sobre campos no encontrados, datos inciertos, o ambigüedades. |
| `processing_time_ms` | `integer` | Tiempo total de procesamiento en milisegundos (útil para métricas). |

### Notas sobre `extracted_data`

- Las **claves deben coincidir** con los `field.key` definidos en la plantilla (`template_structure_snapshot`).
- Si un campo de la plantilla **no aparece** en la transcripción, incluir la clave con valor `null` o `undefined` (el frontend maneja ambos).
- El frontend no filtra ni valida las claves — confía en que el backend solo devuelve claves que existen en la plantilla.

---

## 5. Códigos de Error

| Código | Cuándo ocurre | Response Body |
|--------|--------------|---------------|
| **400** | `template_id` no existe, o no pertenece al tenant/organización del usuario | `{ "message": "Plantilla no válida" }` |
| **422** | `transcript` vacío o solo espacios; `template_id` faltante | `{ "message": "La transcripción no puede estar vacía" }` o `{ "message": "template_id es requerido" }` |
| **404** | El informe (`id`) no existe | `{ "message": "Informe no encontrado" }` |
| **403** | Usuario sin permiso para editar el informe | `{ "message": "No tienes permisos" }` |
| **500** | Error del LLM (timeout, rate limit, respuesta malformada, etc.) | `{ "message": "Error al procesar con IA" }` |
| **503** | Servicio de IA no disponible | `{ "message": "Servicio de IA temporalmente no disponible" }` |

### Manejo de errores 500 del LLM

El frontend **no reintenta automáticamente**. Si devuelves 500, el frontend muestra el mensaje de error al usuario y le permite reintentar manualmente.

Si el LLM devuelve una respuesta que no es JSON válido:
- Reintenta internamente 1 vez (máximo)
- Si sigue fallando, devuelve 500

---

## 6. Prompting del LLM (recomendación)

El backend debe preparar un prompt que incluya:

1. **La estructura de la plantilla** (`template_structure_snapshot`):
   - Lista de campos con: `key`, `label`, `type`, `ai_help_description`
2. **El texto de la transcripción** (`transcript`)
3. **Instrucciones de comportamiento**:

```
Eres un asistente médico experto. Extrae la información clínica del siguiente texto
de transcripción y complétala en el formato JSON solicitado.

REGLAS:
- Responde ÚNICAMENTE en formato JSON válido
- Las claves del JSON deben coincidir EXACTAMENTE con los field.key proporcionados
- Si no hay información para un campo, usa null (no omitas la clave)
- No inventes datos que no aparezcan en la transcripción
- Mantén la terminología médica exacta del texto original

PLANTILLA:
{ field.key: "motivo_consulta", label: "Motivo de consulta", type: "text", ai_help_description: "Razón por la que el paciente acude a consulta" }
{ field.key: "diagnostico", label: "Diagnóstico", type: "text", ai_help_description: "Diagnóstico principal de la consulta" }
...

TRANSCRIPCIÓN:
{transcript}
```

### Structured Outputs / JSON Mode

**Obligatorio**: Forzar al LLM a responder en JSON válido. Opciones:
- OpenAI: `response_format: { type: "json_object" }`
- Anthropic: Pedir JSON en el system prompt + validar parseo
- Ollama/Local: Especificar `format: "json"` en la request

El backend debe **validar que la respuesta sea JSON parseable** antes de devolverla al frontend. Si no lo es, reintentar 1 vez y luego devolver 500.

---

## 7. Contrato con el Endpoint de Transcripción

Este endpoint se llama **después** de `/reports/{id}/transcribe`. El flujo completo es:

```
POST /reports/{id}/transcribe
  → Response: { transcript, segments, language, duration_seconds }

POST /reports/{id}/extract-data
  Body: { transcript: <transcript>, template_id: <id> }
  → Response: { extracted_data, confidence_scores, warnings, processing_time_ms }
```

El frontend extrae `transcript` del resultado de `/transcribe` y lo pasa a `/extract-data`.

---

## 8. Consideraciones de Implementación

### Seguridad
- Validar que el usuario tenga permiso para editar el informe (`report.user_id` o roles)
- Validar que `template_id` pertenezca a la organización del usuario
- No guardar transcripciones ni resultados del LLM en logs (contienen datos médicos)

### Performance
- Timeout recomendado: **30 segundos** (el frontend espera máximo 30s)
- El LLM puede tardar 1-5 segundos dependiendo del tamaño de la transcripción
- Considerar cola de jobs (Laravel Queue) si el procesamiento es lento

### Plantilla
- El backend necesita acceso a `template_structure_snapshot` para construir el prompt
- Puede cargarse desde la tabla `report_templates` usando `template_id`

### Logs
- Loggear `processing_time_ms`, `template_id`, tamaño del `transcript` (en caracteres)
- NO loggear el contenido del transcript ni los datos extraídos (PII/PHI)
