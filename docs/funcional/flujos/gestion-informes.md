# Flujo de API — Gestión de Informes

## Secuencia Principal (Crear → Editar → Firmar → Cerrar → Descargar)

### 1. Iniciar Informe

Crear un nuevo informe en estado `draft`.

**Request:**
```
POST /api/reports
Authorization: Bearer <token>
Content-Type: application/json

{
  "patient_id": 1,
  "template_id": 2,
  "template_structure_snapshot": { ... }
}
```

**Response (201):**
```json
{
  "id": 42,
  "patient_id": 1,
  "user_id": 1,
  "status": "draft",
  "values": {},
  ...
}
```

**Permiso requerido:** `report.create`

---

### 2. Guardar Borrador

Actualizar los valores del informe. Se puede llamar múltiples veces.

**Request:**
```
PUT /api/reports/42
Authorization: Bearer <token>
Content-Type: application/json

{
  "values": {
    "hcg_motivo_consulta": "Dolor torácico",
    "hcg_enfermedad_actual": "Paciente de 45 años..."
  }
}
```

**Response (200):** El informe actualizado con los nuevos valores.

**Permiso requerido:** `report.edit`
**Restricciones:** Solo en estado `draft`. Solo el autor.

---

### 3. Firmar Informe

Firma electrónica del informe. Cambia estado a `signed`.

**Request:**
```
POST /api/reports/42/sign
Authorization: Bearer <token>
Content-Type: application/json

{
  "signature": "data:image/png;base64,iVBORw0KGgo..."
}
```

**Response (200):** Informe con `status: "signed"`, `signature_path` y `signed_at`.

**Permiso requerido:** `report.sign`
**Restricciones:** Solo en estado `draft`. Solo el autor. La firma debe ser base64 válida.

---

### 4. Cerrar Informe

Genera el PDF y cambia estado a `closed`.

**Request:**
```
POST /api/reports/42/close
Authorization: Bearer <token>
```

**Response (200):** Informe con `status: "closed"`, `pdf_path` y `closed_at`.

**Permiso requerido:** `report.close`
**Restricciones:** Solo en estado `signed`. Solo el autor.

---

### 5. Descargar PDF

Descarga el PDF del informe.

**Request:**
```
GET /api/reports/42/pdf
Authorization: Bearer <token>
```

**Response:** `BinaryFileResponse` con `Content-Type: application/pdf`.
- Nombre de archivo: `informe_42.pdf`

**Permiso requerido:** `report.download-pdf`
**Restricciones:** Disponible solo para informes `signed` o `closed`. Si el PDF no existe (ej. firmado pero no cerrado), se regenera automáticamente.

---

## Flujo de Transcripción y Extracción con IA

### 6. Transcribir Audio

**Request:**
```
POST /api/reports/42/transcribe
Authorization: Bearer <token>
Content-Type: multipart/form-data

audio: @dictado.mp3
language: es
diarization: true
```

**Response (200):**
```json
{
  "data": {
    "transcript": "Paciente de 45 años...",
    "segments": [
      { "speaker": "Médico", "text": "¿Cómo se ha sentido?", "start": 0.0, "end": 3.2 },
      { "speaker": "Paciente", "text": "He tenido dolor.", "start": 3.5, "end": 7.1 }
    ],
    "language": "es",
    "duration_seconds": 15.3
  }
}
```

**Permiso requerido:** `report.edit`

---

### 7. Extraer Datos con IA

**Request:**
```
POST /api/reports/42/extract-data
Authorization: Bearer <token>
Content-Type: application/json

{
  "transcript": "Paciente de 45 años...",
  "template_id": 2
}
```

**Response (200):**
```json
{
  "data": {
    "extracted_data": {
      "hcg_motivo_consulta": "Dolor torácico",
      "hcg_ta": "120/80"
    },
    "confidence_scores": { "hcg_motivo_consulta": 0.95 },
    "warnings": ["Campo 'alergias' no encontrado"],
    "processing_time_ms": 1250
  }
}
```

**Permiso requerido:** `report.edit`

---

## Flujos de Error

### 401 — No autenticado
```json
{
  "error": "Unauthorized",
  "message": "Token not provided"
}
```
**Causa:** JWT faltante, expirado o inválido.

### 403 — Permiso denegado
```json
{
  "message": "Missing required permission: report.sign"
}
```
**Causa:** El usuario no tiene el permiso requerido para la operación.

### 404 — Informe no encontrado
```json
{
  "message": "Informe no encontrado"
}
```
**Causa:** El ID del informe no existe.

### 409 — Conflicto (plantilla en uso)
```json
{
  "message": "No se puede eliminar la plantilla porque tiene informes de pacientes asociados"
}
```
**Causa:** Al eliminar una plantilla que tiene informes asociados.

### 422 — Validación / Estado incorrecto
```json
{
  "message": "Solo se pueden firmar informes en estado borrador"
}
```
**Causa:** Operación no válida para el estado actual del informe (ej. firmar un informe ya firmado).

### 500 — Error interno
```json
{
  "message": "Internal server error"
}
```
**Causa:** Error inesperado del servidor, timeout de IA, etc.

---

## Diagrama de Estados

```
                    ┌──────────┐
                    │  draft   │
                    └────┬─────┘
                         │ sign
                    ┌────▼─────┐
                    │  signed  │
                    └────┬─────┘
                         │ close
                    ┌────▼─────┐
                    │  closed  │
                    └──────────┘

Transiciones:
  draft  → signed : POST /reports/{id}/sign
  signed → closed : POST /reports/{id}/close

Lectura/descarga permitida en:
  signed : GET /reports/{id}, GET /reports/{id}/pdf
  closed : GET /reports/{id}, GET /reports/{id}/pdf
```
