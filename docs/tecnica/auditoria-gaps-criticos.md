# Auditoría de Gaps Críticos — MateriaGris API

## Metodología
Análisis del código fuente (`routes/api.php`, `app/`, `database/`) comparado contra las necesidades del frontend y las funcionalidades documentadas.

---

## Críticos

### 1. DELETE de Pacientes no implementado
- **Módulo:** Patients
- **Problema:** No existe endpoint `DELETE /api/patients/{id}`. Solo hay GET, POST, PUT.
- **Impacto:** El frontend no puede eliminar pacientes (ni siquiera soft delete).
- **Solución:** Implementar `DeletePatientAction` + `DeletePatientCommand` con soft delete.

### 2. Sin paginación en listados
- **Módulo:** Admin — Users, Admin — Roles, Patients
- **Problema:** Los endpoints `GET /api/admin/users`, `GET /api/admin/roles` y `GET /api/patients/find` no implementan paginación.
- **Impacto:** Con muchos registros, la respuesta será pesada y lenta.
- **Solución:** Agregar parámetros `page`, `per_page` y usar `paginate()` de Laravel.

### 3. Sin tests de integración para módulos admin
- **Módulo:** Admin — Users, Roles, Permissions
- **Problema:** No se encontraron tests feature para los endpoints de administración.
- **Impacto:** Riesgo de regresión al modificar la lógica de permisos.
- **Solución:** Crear tests para cada endpoint CRUD de admin.

### 4. Pacientes sin Commands dedicados
- **Módulo:** Patients
- **Problema:** `GetPatientsAction` y `CreatePatientAction` contienen lógica directa en lugar de delegar a Commands. Solo `UpdatePatientAction` usa un Command existente de Admin.
- **Impacto:** Inconsistencia arquitectónica; viola el patrón hexagonal.
- **Solución:** Extraer lógica de pacientes a Commands en `app/Commands/Patients/`.

---

## Medios

### 5. Validación de requests incompleta
- **Módulo:** Patients
- **Problema:** No existen FormRequests dedicados para pacientes (solo hay `CreateUserRequest` y `UpdateUserRequest` en Admin).
- **Impacto:** Validación mezclada con lógica de negocio.
- **Solución:** Crear `StorePatientRequest` y `UpdatePatientRequest`.

### 6. Sin caché de permisos invalidada automáticamente
- **Módulo:** RBAC
- **Problema:** `PermissionService::invalidateCacheForUser()` debe llamarse manualmente desde cada servicio que modifica permisos.
- **Impacto:** Los cambios de permisos pueden no reflejarse hasta invalidación manual.
- **Solución:** Implementar listeners/model events para invalidación automática al cambiar roles/permisos.

### 7. Sin eventos de auditoría en endpoints clave
- **Módulo:** Auth, Admin
- **Problema:** No todos los endpoints críticos registran eventos de auditoría (ej. login fallido, cambios de rol).
- **Impacto:** Dificulta el rastreo de actividades sospechosas.
- **Solución:** Agregar `AuditService::record()` en login, logout, creación/eliminación de usuarios y roles.

### 8. Sin manejo de errores estandarizado
- **Módulo:** Global
- **Problema:** Las excepciones no tienen un formato uniforme de respuesta JSON.
- **Impacto:** El frontend debe manejar múltiples formatos de error.
- **Solución:** Implementar `ApiExceptionHandler` que estandarice respuestas de error.

---

## Bajos

### 9. Sin endpoints para health check detallado
- **Módulo:** Health
- **Problema:** `GET /api/health` solo verifica que el servidor responde.
- **Impacto:** No se puede monitorear estado de BD, Redis, colas.
- **Solución:** Expandir health check para verificar dependencias.

### 10. Documentación faltante de módulos
- **Problema:** `docs/` contenía solo 5 archivos (ahora reorganizados).
- **Solución:** **(Completado)** — Documentación funcional y técnica generada.

---

## Tabla de Acciones Recomendadas

| # | Gravedad | Acción | Esfuerzo | Prioridad |
|---|----------|--------|----------|-----------|
| 1 | Crítico | Implementar DELETE patient | 1-2h | Alta |
| 2 | Crítico | Agregar paginación a listados | 2-3h | Alta |
| 3 | Crítico | Escribir tests de admin | 4-6h | Alta |
| 4 | Crítico | Extraer Commands de patients | 2-3h | Media |
| 5 | Medio | Crear FormRequests de patients | 1h | Media |
| 6 | Medio | Invalidación automática de caché | 3-4h | Media |
| 7 | Medio | Auditoría en endpoints clave | 2-3h | Baja |
| 8 | Medio | Estandarizar errores JSON | 2-3h | Baja |
| 9 | Bajo | Health check detallado | 1-2h | Baja |
