# Proposal: Extracción de datos clínicos con IA desde transcripción

## Intent

Permitir que los profesionales médicos dicten informes y obtengan campos clínicos extraídos automáticamente mediante un LLM, reduciendo el tiempo de completado manual y mejorando la precisión del volcado de datos.

## Scope

### In Scope
- Endpoint `POST /reports/{id}/extract-data` — recibe transcript + template_id, devuelve datos extraídos con scores de confianza e instrucciones de warnings
- Servicio `LlmExtractorService` con prompt builder, llamada HTTP a API OpenAI-compatible, parseo JSON + 1 reintento
- Validación de entrada: transcript no vacío, template_id existente y activo
- Control de acceso: reutiliza permiso `report.edit` (mismo gate que SaveDraftReport)
- Timeout HTTP 30s, respuesta síncrona (sin colas)
- Configuración LLM via variables de entorno (`LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`, `LLM_BASE_URL`)
- Logging seguro: solo métricas (template_id, processing_time_ms, largo de transcripción), NUNCA PII/PHI

### Out of Scope
- Colas asíncronas (Laravel Queue) — se evaluará si el volumen lo justifica
- Validación de tipos de campos extraídos (se pasa through al frontend)
- Soporte para múltiples providers LLM con adaptadores (KISS: OpenAI-compatible primero)
- UI del frontend (muestra campos con side-by-side)
- Endpoint de transcripción de audio (se asume que `/reports/{id}/transcribe` ya existe o lo hará otro change)

## Capabilities

### New Capabilities
- `report-extract-data`: Extracción de datos clínicos estructurados desde transcripción de audio usando LLM

### Modified Capabilities
- None

## Approach

**Arquitectura hexagonal estándar** siguiendo el patrón Action → Command → Services/Repositories:

```
ExtractReportDataAction
  → ExtractReportDataRequest (validation)
  → ExtractReportDataCommand
    → PermissionService::ensure(user, 'report.edit')
    → PatientReportReadRepository::buscarPorId(id)
    → ReportTemplateReadRepository::buscarPorId(templateId)
    → LlmExtractorService::extract(template, transcript)
    → retorna DTO con extracted_data, confidence_scores, warnings, processing_time_ms
```

**Decisiones técnicas clave**:
- Clave de extracción: `field` del esquema de template (no `field.key`)
- Descripción para el LLM: `label` como fallback (no existe `ai_help_description` en el schema actual)
- Provider LLM: llamada cruda via `Http` facade de Laravel a API OpenAI-compatible (sin paquetes externos)
- Prompt: system message con instrucciones clínicas + JSON de template + transcripción; `response_format: json_object`
- Reintento: 1 solo reintento si respuesta no es JSON parseable; si falla, 500

### New files

| File | Purpose |
|---|---|
| `app/Http/Actions/Reports/ExtractReportDataAction.php` | Action invocable, orquesta request → command → JSON response |
| `app/Http/Requests/Reports/ExtractReportDataRequest.php` | Valida transcript (required string, not blank) y template_id (required int) |
| `app/Commands/Reports/ExtractReportDataCommand.php` | Caso de uso: permisos, búsqueda de report/template, delegación a LLM service |
| `app/Services/LlmExtractorService.php` | Prompt builder, HTTP call, JSON parsing, retry, logging seguro |
| `app/DTOs/ExtractReportDataResult.php` | DTO inmutable con extracted_data, confidence_scores, warnings, processing_time_ms |
| `database/migrations/{timestamp}_add_report_llm_config.php` | Agrega `LLM_PROVIDER` y `LLM_MODEL` a `system_variables` |
| `tests/Feature/Actions/Reports/ExtractReportDataTest.php` | Feature test: happy path, auth, permisos, validación, errores LLM |

### Modified files

| File | Change |
|---|---|
| `routes/api.php` | Nueva ruta `POST /reports/{id}/extract-data → ExtractReportDataAction` con middleware `auth.jwt` + `require_permissions:report.edit` |
| `config/services.php` | Sección `llm` con provider, api_key, model, base_url desde env |
| `.env.example` | Agregar `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`, `LLM_BASE_URL` |
| `.env` | Agregar mismas variables con valores de desarrollo |

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Actions/Reports/` | New | ExtractReportDataAction |
| `app/Commands/Reports/` | New | ExtractReportDataCommand |
| `app/Services/` | New | LlmExtractorService |
| `routes/api.php` | Modified | Nueva ruta POST bajo grupo reports |
| `config/services.php` | Modified | Nueva sección llm |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| LLM devuelve JSON inválido incluso tras reintento | Medium | 1 retry interno; si falla, 500 con mensaje genérico. Frontend permite reintento manual. |
| Timeout del LLM (30s) | Low | Http facade con `timeout(30)`. Si excede, 503. |
| PII/PHI en logs | Low | Solo loggear métricas sin contenido. Revisión de código obligatoria. |
| Campos `label` insuficientes para guiar al LLM | Medium | Funciona para campos estándar. Si se necesita `ai_help_description`, se agrega al schema de template en iteración futura. |

## Rollback Plan

1. Comentar o eliminar la ruta en `routes/api.php`
2. Revertir cambios en `config/services.php`
3. Si se usó migración de system_variables: rollback migration
4. No hay cambios de schema en tablas core (patient_reports, report_templates) — sin riesgo de datos

## Dependencies

- API OpenAI-compatible accesible desde el servidor (configurable via env)
- `auth.jwt` middleware (existente)
- `require_permissions` middleware (existente)
- `PatientReportReadRepository`, `ReportTemplateReadRepository` (existentes)

## Success Criteria

- [ ] `POST /reports/{id}/extract-data` con transcript + template_id válidos devuelve 200 con datos extraídos
- [ ] Usuario sin permiso `report.edit` recibe 403
- [ ] Transcript vacío recibe 422
- [ ] Template_id inválido recibe 400
- [ ] Timeout o fallo del LLM devuelve 500/503, NO crashea
- [ ] Logs NO contienen transcript ni datos extraídos
- [ ] Test de feature cubre happy path + todos los errores
