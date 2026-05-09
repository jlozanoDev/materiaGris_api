# Módulo de Consultas de Pacientes — Propuesta de Diseño

> **Estado:** Planificado — No implementado.
>
> Documento original: `consultations-module.md` — reorganizado en esta ubicación.

---

## 1. Propósito

Gestionar el ciclo de vida completo de una consulta médica: registro de la consulta, planes de tratamiento, órdenes de laboratorio, recetas, imagenología y seguimiento.

## 2. Arquitectura Propuesta

Sigue el patrón hexagonal del proyecto:

```
Route → Action (HTTP adapter) → Command (use case) → Repository → Model
```

Namespaces propuestos:

| Capa | Namespace |
|------|-----------|
| Action | `App\Http\Actions\Consultations\` |
| Command | `App\Commands\Consultations\` |
| Repository | `App\Repositories\Consultation\` |
| Model | `App\Models\Consultation`, `Prescription`, etc. |

## 3. Estructura de Base de Datos

7 nuevas tablas:

### `patient_consultations` (core)
- `patient_id`, `doctor_id`, `created_by` (FK)
- Datos clínicos: `consultation_date`, `type`, `status`, `reason`, `history`, `physical_exam`, `assessment`, `plan`
- Flags: `requires_lab`, `requires_imaging`, `requires_referral`
- Soft delete, timestamps

### `prescriptions`
- `consultation_id`, `patient_id`, `prescribed_by`
- `status` (active, dispensed, cancelled, completed)

### `prescription_items`
- `prescription_id`
- `medication`, `dosage`, `frequency`, `route`, `duration`, `quantity`

### `lab_orders`
- `consultation_id`, `patient_id`, `ordered_by`
- `order_number`, `status`, `priority`, `collected_at`

### `lab_order_items`
- `lab_order_id`
- `test_name`, `test_code`, `specimen`, `result`, `reference_range`, `unit`

### `imaging_orders`
- `consultation_id`, `patient_id`, `ordered_by`
- `study_type`, `body_part`, `status`, `findings`, `impression`

### `consultation_attachments`
- `consultation_id`, `uploaded_by`
- `filename`, `path`, `mime_type`, `size`, `category`

## 4. Permisos RBAC (nuevos)

Categoría: `Consultations` → slug: `consultations`

| Permiso | Descripción |
|---------|-------------|
| `consultation.view` | Ver consultas |
| `consultation.create` | Crear consultas |
| `consultation.update` | Actualizar consultas |
| `consultation.delete` | Eliminar consultas (soft delete) |
| `consultation.sign` | Firmar / cerrar consultas |

## 5. Endpoints de API Propuestos

```
GET    /api/consultations                     → ListConsultationsAction
GET    /api/consultations/{id}                → GetConsultationAction
POST   /api/consultations                     → CreateConsultationAction
PUT    /api/consultations/{id}                → UpdateConsultationAction
DELETE /api/consultations/{id}                → DeleteConsultationAction
POST   /api/consultations/{id}/sign           → SignConsultationAction
GET    /api/consultations/{id}/prescriptions   → GetPrescriptionsAction
POST   /api/consultations/{id}/prescriptions   → CreatePrescriptionAction
GET    /api/consultations/{id}/lab-orders      → GetLabOrdersAction
POST   /api/consultations/{id}/lab-orders      → CreateLabOrderAction
GET    /api/consultations/{id}/imaging-orders  → GetImagingOrdersAction
POST   /api/consultations/{id}/imaging-orders  → CreateImagingOrderAction
GET    /api/consultations/{id}/attachments     → GetAttachmentsAction
POST   /api/consultations/{id}/attachments     → UploadAttachmentAction
DELETE /api/consultations/attachments/{id}     → DeleteAttachmentAction
```

## 6. Orden de Migraciones

```
1. create_patient_consultations_table
2. create_prescriptions_table
3. create_prescription_items_table
4. create_lab_orders_table
5. create_lab_order_items_table
6. create_imaging_orders_table
7. create_consultation_attachments_table
8. add_consultations_permissions
```

## 7. Notas de Implementación

| Aspecto | Decisión |
|---------|----------|
| Soft deletes | En todas las tablas principales |
| Cascading | FK a patient_id y consultation_id usan `cascadeOnDelete` |
| Validación | En Action con `$request->validate()` (patrón existente) |
| Auditoría | Usar tabla `audits` existente para eventos importantes |
| Status | String con índice, no ENUM (patrón existente) |

---

## Referencias

- Documento original: `docs/consultations-module.md` (archivado)
- Diseño de permisos: [`docs/tecnica/modelo-permisos-roles.md`](./modelo-permisos-roles.md)
- Arquitectura del proyecto: [`docs/tecnica/arquitectura.md`](./arquitectura.md)
