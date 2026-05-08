# Módulo de Consultas de Pacientes — Análisis Técnico

## 1. Propósito

Gestionar el ciclo de vida completo de una consulta médica: registro de la consulta, planes de tratamiento, órdenes de laboratorio, recetas, imagenología y seguimiento.

## 2. Arquitectura

Sigue el patrón existente del proyecto:

```
Route → Action (HTTP adapter) → Command (use case) → Repository (infrastructure) → Model (domain)
```

### Namespaces propuestos

| Capa | Namespace | Ejemplo |
|------|-----------|---------|
| **Action** | `App\Http\Actions\Consultations\` | `CreateConsultationAction` |
| **Command** | `App\Commands\Consultations\` | `CreateConsultationCommand` |
| **Repository** | `App\Repositories\Consultation\` | `SaveConsultationRepository` |
| **Model** | `App\Models\` | `Consultation`, `Prescription`, `LabOrder`, `ImagingOrder` |

> A diferencia de `Patient` (Commands en `Admin\` pero Actions en `Patients\`), se propone mantener **todo** bajo `Consultations\` para no mezclar dominios.

## 3. Estructura de Base de Datos

### 3.1 `patient_consultations` — Consulta médica (core)

```php
Schema::create('patient_consultations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
    $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

    // Clinical
    $table->dateTime('consultation_date');            // Fecha/hora de la consulta
    $table->string('type', 50)->index();              // 'office', 'emergency', 'follow_up', 'telemedicine'
    $table->string('status', 50)->index();            // 'scheduled', 'in_progress', 'completed', 'cancelled'
    $table->text('reason')->nullable();               // Motivo de consulta (subjective)
    $table->text('history')->nullable();              // Historia de la enfermedad actual
    $table->text('physical_exam')->nullable();        // Hallazgos del examen físico
    $table->text('assessment')->nullable();           // Evaluación / impresión diagnóstica
    $table->text('plan')->nullable();                 // Plan de tratamiento / management

    $table->string('follow_up_instructions', 500)->nullable();
    $table->date('follow_up_date')->nullable();       // Próxima cita sugerida

    // Flags
    $table->boolean('requires_lab')->default(false);
    $table->boolean('requires_imaging')->default(false);
    $table->boolean('requires_referral')->default(false);

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['patient_id', 'consultation_date']);
    $table->index(['doctor_id', 'consultation_date']);
    $table->index('type');
    $table->index('status');
});
```

### 3.2 `prescriptions` — Recetas / Órdenes de medicación

```php
Schema::create('prescriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consultation_id')->constrained('patient_consultations')->cascadeOnDelete();
    $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
    $table->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('status', 50)->default('active');  // 'active', 'dispensed', 'cancelled', 'completed'
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index(['patient_id', 'status']);
});
```

### 3.3 `prescription_items` — Medicamentos individuales en una receta

```php
Schema::create('prescription_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
    $table->string('medication', 255);                    // Nombre del medicamento
    $table->string('dosage', 100);                        // Ej: "500mg"
    $table->string('frequency', 100);                     // Ej: "cada 8 horas"
    $table->string('route', 50)->nullable();              // 'oral', 'topical', 'iv', 'im'
    $table->string('duration', 100)->nullable();          // Ej: "7 días"
    $table->string('instructions', 500)->nullable();      // Instrucciones adicionales
    $table->unsignedInteger('quantity')->nullable();
    $table->boolean('requires_refill')->default(false);
    $table->unsignedTinyInteger('refill_count')->default(0);
    $table->unsignedTinyInteger('order')->default(0);
    $table->timestamps();

    $table->index('prescription_id');
});
```

### 3.4 `lab_orders` — Órdenes de laboratorio

```php
Schema::create('lab_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consultation_id')->constrained('patient_consultations')->cascadeOnDelete();
    $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
    $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('order_number', 50)->nullable()->unique();   // Número de orden externo
    $table->string('status', 50)->default('pending');           // 'pending', 'collected', 'processing', 'completed', 'cancelled'
    $table->string('priority', 20)->default('normal');          // 'normal', 'urgent', 'stat'
    $table->date('collected_at')->nullable();                    // Fecha de toma de muestra
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index(['patient_id', 'status']);
    $table->index('order_number');
});
```

### 3.5 `lab_order_items` — Pruebas específicas en una orden

```php
Schema::create('lab_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
    $table->string('test_name', 255);                           // Nombre de la prueba
    $table->string('test_code', 50)->nullable()->index();       // Código interno/LOINC
    $table->string('specimen', 100)->nullable();                // 'blood', 'urine', 'stool', etc.
    $table->text('result')->nullable();                         // Valor del resultado
    $table->text('reference_range')->nullable();                // Rango de referencia
    $table->string('unit', 50)->nullable();                     // Unidad de medida
    $table->string('status', 50)->default('pending');           // 'pending', 'completed', 'abnormal', 'cancelled'
    $table->boolean('is_abnormal')->nullable();
    $table->text('notes')->nullable();
    $table->unsignedTinyInteger('order')->default(0);
    $table->timestamps();

    $table->index(['lab_order_id', 'status']);
    $table->index('test_code');
});
```

### 3.6 `imaging_orders` — Órdenes de imagenología

```php
Schema::create('imaging_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consultation_id')->constrained('patient_consultations')->cascadeOnDelete();
    $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
    $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('study_type', 100);                        // 'xray', 'ct_scan', 'mri', 'ultrasound', 'mammography'
    $table->string('body_part', 100)->nullable();             // 'chest', 'abdomen', 'head', etc.
    $table->string('status', 50)->default('pending');         // 'pending', 'scheduled', 'completed', 'cancelled'
    $table->text('findings')->nullable();                     // Resultados / hallazgos
    $table->text('impression')->nullable();                   // Impresión radiológica
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index(['patient_id', 'status']);
});
```

### 3.7 `consultation_attachments` — Archivos adjuntos (órdenes escaneadas, documentos)

```php
Schema::create('consultation_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consultation_id')->constrained('patient_consultations')->cascadeOnDelete();
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('filename', 255);                          // Nombre original
    $table->string('path', 500);                              // Ruta en disco
    $table->string('mime_type', 100);
    $table->unsignedInteger('size');                          // Bytes
    $table->string('category', 50)->nullable();               // 'lab_result', 'imaging', 'referral', 'consent', 'other'
    $table->text('description')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('consultation_id');
    $table->index('category');
});
```

## 4. Resumen de Entidades y Relaciones

```
Patient (existing)
  └── patient_consultations (1:N)
        ├── prescriptions (1:N)
        │     └── prescription_items (1:N)
        ├── lab_orders (1:N)
        │     └── lab_order_items (1:N)
        ├── imaging_orders (1:N)
        └── consultation_attachments (1:N)
```

## 5. Permisos RBAC (nuevos)

Siguiendo el patrón existente (`patient.view`, `patient.create`):

```php
Categoría: 'Consultas' → slug: 'consultations' (order: 30)

Permisos:
  - consultation.view     → "Ver consultas"
  - consultation.create   → "Crear consultas"
  - consultation.update   → "Actualizar consultas"
  - consultation.delete   → "Eliminar consultas" (borrado lógico)
  - consultation.sign     → "Firmar / cerrar consultas"
```

Migración de permisos de ejemplo:

```php
// 2026_05_09_000001_add_consultations_permissions.php

$category = PermissionCategory::create([
    'name' => 'Consultas',
    'slug' => 'consultations',
    'description' => 'Gestión de consultas médicas',
    'order' => 30,
]);

$permissions = [
    ['name' => 'Ver consultas',         'slug' => 'consultation.view',   'action' => 'view'],
    ['name' => 'Crear consultas',       'slug' => 'consultation.create', 'action' => 'create'],
    ['name' => 'Actualizar consultas',  'slug' => 'consultation.update', 'action' => 'update'],
    ['name' => 'Eliminar consultas',    'slug' => 'consultation.delete', 'action' => 'delete'],
    ['name' => 'Firmar consultas',      'slug' => 'consultation.sign',   'action' => 'sign'],
];

foreach ($permissions as $perm) {
    $perm['category_id'] = $category->id;
    Permission::create($perm);
}
```

## 6. Endpoints de API Propuestos

```php
Route::prefix('consultations')->middleware('auth.jwt')->group(function () {
    Route::get('/', ListConsultationsAction::class)
        ->middleware('require_permissions:consultation.view');

    Route::get('/{id}', GetConsultationAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:consultation.view');

    Route::post('/', CreateConsultationAction::class)
        ->middleware('require_permissions:consultation.create');

    Route::put('/{id}', UpdateConsultationAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:consultation.update');

    Route::delete('/{id}', DeleteConsultationAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:consultation.delete');

    Route::post('/{id}/sign', SignConsultationAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:consultation.sign');

    // Sub-recursos
    Route::get('/{id}/prescriptions', GetPrescriptionsAction::class);
    Route::post('/{id}/prescriptions', CreatePrescriptionAction::class);

    Route::get('/{id}/lab-orders', GetLabOrdersAction::class);
    Route::post('/{id}/lab-orders', CreateLabOrderAction::class);

    Route::get('/{id}/imaging-orders', GetImagingOrdersAction::class);
    Route::post('/{id}/imaging-orders', CreateImagingOrderAction::class);

    Route::get('/{id}/attachments', GetAttachmentsAction::class);
    Route::post('/{id}/attachments', UploadAttachmentAction::class);
    Route::delete('/attachments/{attachmentId}', DeleteAttachmentAction::class);
});
```

## 7. Migraciones — Orden de Creación

```
2026_05_09_000001_create_patient_consultations_table.php
2026_05_09_000002_create_prescriptions_table.php
2026_05_09_000003_create_prescription_items_table.php
2026_05_09_000004_create_lab_orders_table.php
2026_05_09_000005_create_lab_order_items_table.php
2026_05_09_000006_create_imaging_orders_table.php
2026_05_09_000007_create_consultation_attachments_table.php
2026_05_09_000008_add_consultations_permissions.php
```

## 8. Notas de Implementación

| Aspecto | Decisión |
|---------|----------|
| **Soft deletes** | En `patient_consultations`, `prescriptions`, `lab_orders`, `imaging_orders`, `consultation_attachments` |
| **Cascading** | FK → `patient_id` y FK → `consultation_id` usan `cascadeOnDelete` |
| **Validación** | Se hace en Action con `$request->validate()` (patrón existente) |
| **Auditoría** | Usar la tabla `audits` existente para eventos importantes (creación, firma, cancelación) |
| **Nombres de métodos** | En repositorios: `buscarPorFiltros()`, `crear()`, `actualizar()` (patrón existente) |
| **Status** | String con índice, no ENUM (patrón existente) |
