# Contrato Backend: Módulo de Dictado IA

> Extraído de `openspec/changes/requisitos-modulo-dictado-ia/`  
> Destinatario: IA del backend (Laravel). Frontend: Vue 3, `fetchClient` con JWT.

---

## 1. POST `/api/reports/{id}/transcribe`

Transcribe audio con diarización (separación de hablantes).

### Request

- **Content-Type**: `multipart/form-data`
- **Timeout esperado**: 120s (archivos de audio grandes)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `audio` | File (Blob) | Sí | Audio en WebM/Opus, WAV, MP3, MP4, OGG |
| `diarization` | bool | Sí | Si el backend debe identificar hablantes |
| `language` | string | No | Código idioma (ej. `"es"`). Si se omite, autodetectar. |

### Response 200

```json
{
  "transcript": "Texto completo de la consulta transcrita...",
  "segments": [
    {
      "speaker": "Speaker 1",
      "text": "¿Cómo se ha sentido esta semana?",
      "start": 0.0,
      "end": 3.2
    },
    {
      "speaker": "Speaker 2",
      "text": "He tenido mucho dolor en las rodillas.",
      "start": 3.5,
      "end": 7.1
    }
  ],
  "language": "es",
  "duration_seconds": 125.4
}
```

### Reglas de diarización

- `speaker` usa el formato `"Speaker N"` (no roles — el frontend añade "(Médico)" / "(Paciente)").
- `text` es el fragmento transcrito para ese hablante.
- `start`/`end` en segundos desde el inicio del audio.
- Si `diarization=false`, devolver un solo segmento con `"speaker": "Speaker 1"`.

### Errores

| Código | Significado | Acción frontend |
|--------|-------------|-----------------|
| `422` | Formato de audio no soportado | Muestra error, botón "Reintentar" |
| `413` | Archivo demasiado grande | Muestra error con sugerencia de tamaño máximo |
| `500` | Fallo del motor de transcripción | Muestra error, botón "Reintentar" |

---

## 2. POST `/api/reports/{id}/extract-data`

Envía transcripción a un LLM para extraer datos estructurados y mapearlos a campos del informe.

### Request

- **Content-Type**: `application/json`
- **Timeout esperado**: 60s

```json
{
  "transcript": "Texto completo de la transcripción...",
  "template_id": 42
}
```

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `transcript` | string | Sí | Texto completo (con o sin etiquetas de hablante) |
| `template_id` | integer | Sí | ID de la plantilla; sus campos tienen `ai_help_description` |

### Response 200

```json
{
  "extracted_data": {
    "edad": "45",
    "diagnostico": "Artritis reumatoide",
    "antecedentes": "Hipertensión, diabetes tipo 2",
    "medicacion_actual": "Metformina 850mg"
  },
  "confidence_scores": {
    "edad": 0.95,
    "diagnostico": 0.88,
    "antecedentes": 0.91,
    "medicacion_actual": 0.76
  },
  "warnings": [
    "Campo 'alergias' no encontrado en la transcripción"
  ],
  "processing_time_ms": 2340
}
```

### Reglas del contrato `extracted_data`

- Las **keys** del objeto `extracted_data` deben coincidir con los `field.key` de la plantilla (vía `ai_help_description`). El frontend hace un mapeo semántico de 3 niveles como fallback, pero la coincidencia exacta es la primera opción.
- Valores `null`, vacíos (`""`) u omitidos son **válidos** — el frontend los deja en blanco, no los trata como error.
- Los campos sin `ai_help_description` no deben ser objetivo del LLM. El frontend los ignora aunque vengan en `extracted_data`.
- Tipos de campo esperados (según la plantilla): `text`, `textarea`, `number`, `date`, `select`, `multi_select`, `radio`, `checkbox`. Los valores deben ser strings (el frontend convierte al tipo del campo).

### `confidence_scores`

- Opcional. Si se incluye, mapea `field_key → float 0..1`.
- El frontend puede usar esto como tooltip en el futuro (no implementado aún).
- Si no se incluye, el frontend asume 1.0.

### `warnings`

- Array de strings legibles. Se muestran al médico antes de aplicar los datos.
- Usar para: campos no encontrados, baja confianza, ambigüedades detectadas.

### Errores

| Código | Significado | Acción frontend |
|--------|-------------|-----------------|
| `422` | Payload inválido (template_id no existe, transcript vacío) | Muestra error |
| `500` | Fallo del LLM (timeout, JSON malformado, error del modelo) | Muestra error, botón "Reintentar" |

---

## 3. Autenticación

Ambos endpoints requieren autenticación JWT. El frontend envía:

```
Authorization: Bearer <token>
```

El token lo gestiona `useAuthStore` (Pinia) y `fetchClient` lo adjunta automáticamente en cada petición. Si el backend responde `401`, el frontend redirige al login.

---

## 4. Formato de audio aceptado

El frontend usa `MediaRecorder` del navegador. Los MIME types que puede generar:

| Prioridad | MIME type | Navegadores |
|-----------|-----------|-------------|
| 1 (preferido) | `audio/webm;codecs=opus` | Chrome, Firefox, Edge |
| 2 (fallback) | `audio/mp4` | Safari |
| 3 (fallback) | `audio/wav` | Cualquiera (archivos grandes) |

Para upload directo (archivo pregrabado), el input acepta: `.mp3`, `.wav`, `.webm`, `.ogg`, `.mp4`.

El backend debe aceptar al menos WebM/Opus, MP4 y WAV. Si no soporta un formato, responder `422`.

---

## 5. Notas de integración

- **Sin streaming**: El frontend NO usa SSE/WebSocket. Cada endpoint es request→response síncrono. El frontend muestra un spinner durante la espera.
- **Sin reintentos automáticos**: Si falla, el frontend muestra un botón "Reintentar" que reenvía exactamente los mismos datos. El backend no necesita idempotencia por reintento.
- **Sin cancelación**: Una vez lanzada la petición, el frontend solo puede esperar o reintentar tras error. No hay botón de cancelar durante `transcribing`/`analyzing`.
- **Auto-save suprimido**: Mientras el pipeline está en `transcribing` o `analyzing`, el frontend suprime el auto-guardado del formulario. Esto es responsabilidad exclusiva del frontend.
