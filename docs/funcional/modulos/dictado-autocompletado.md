# Módulo de Dictado y Autocompletado de Informes

## Propósito de Negocio

Permitir que los profesionales médicos dicten informes clínicos y obtengan campos estructurados extraídos automáticamente mediante IA. Reduce el tiempo de completado manual y mejora la precisión del volcado de datos clínicos.

## Actores

- Médico (uso diario — dicta el informe y revisa campos extraídos).

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/reports/{id}/extract-data` | POST | Extraer datos clínicos desde una transcripción de audio usando IA |

### POST /api/reports/{id}/extract-data

Recibe una transcripción de texto (proveniente de un servicio de transcripción de audio) y el ID de una plantilla de informe. Usa un modelo de lenguaje (LLM) para extraer los datos clínicos relevantes y devolverlos estructurados según los campos de la plantilla.

**Request:**
```json
{
  "transcript": "Paciente de 45 años, consulta por dolor torácico...",
  "template_id": 7
}
```

**Response (200):**
```json
{
  "data": {
    "extracted_data": {
      "motivo_consulta": "Dolor torácico",
      "diagnostico": "Síndrome coronario agudo",
      "tratamiento": "AAS 100mg, Clopidogrel 75mg"
    },
    "confidence_scores": {
      "motivo_consulta": 0.95,
      "diagnostico": 0.97,
      "tratamiento": 0.94
    },
    "warnings": ["No se encontró información sobre 'alergias'"],
    "processing_time_ms": 1250
  }
}
```

## Criterios de Aceptación

- El endpoint acepta transcript + template_id y devuelve datos extraídos estructurados.
- Los campos extraídos corresponden exactamente a los fields de la plantilla.
- Cada campo incluye un score de confianza (0.0–1.0).
- Si no hay datos para un campo, se incluye con valor `null`.
- Campos en la respuesta del LLM que no existen en la plantilla son descartados.
- El transcript vacío es rechazado (422).
- Template inválida o inactiva es rechazada (400).
- Usuario sin permiso `report.editar` recibe 403.
- Timeout o fallo del LLM no crashea la aplicación (500/503).

## Reglas de Negocio

- La clave de extracción es el campo `field` de la plantilla (no `field.key`).
- La descripción enviada al LLM es `ai_help_description` (fallback: `label` si no existe).
- El contexto del paciente (edad, sexo, últimos 10 informes) se incluye en el prompt sin PII/PHI.
- Todas las llamadas al LLM se persisten en `llm_interactions` para auditoría y trazabilidad.
- No se guarda el contenido del transcript ni los datos extraídos en logs (solo métricas: template_id, tiempo de procesamiento, tamaño del transcript).
- Timeout del LLM: 30 segundos.

## Dependencias

- Permiso: `report.edit` (mismo que guardar borrador de informe).
- Servicio LLM externo configurable (OpenAI-compatible).
- Endpoint de transcripción de audio existente (o futuro).

## Estado de Desarrollo

✅ Completo — endpoint implementado y probado.
