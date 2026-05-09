# Módulo de Pacientes — API

## Propósito de Negocio
Gestionar el catálogo de pacientes de la clínica: registrar nuevos pacientes, buscar existentes y actualizar sus datos demográficos y de contacto.

## Actores
- Médico (uso diario), Recepcionista (futuro, registro y búsqueda).

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/patients/find` | GET | Buscar pacientes por múltiples criterios |
| `/api/patients` | POST | Crear un nuevo paciente |
| `/api/patients/{id}` | PUT | Actualizar datos de un paciente existente |

## Criterios de Aceptación
- La búsqueda debe permitir filtrar por nombre, NSS, email, teléfono.
- La creación debe validar unicidad de NSS y email.
- La actualización debe permitir modificar cualquier campo del paciente.
- No debe ser posible eliminar pacientes vía API (soft delete planificado).

## Reglas de Negocio
- NSS (`national_id`) único a nivel de sistema.
- Email único a nivel de sistema.
- La dirección puede incluir calle, número, colonia, código postal, estado y país.
- El paciente se marca como inactivo (`is_active = false`) en lugar de eliminarse.
- `last_visit_at` se actualiza cuando se registra una consulta (pendiente de implementar).
- Los campos `age` y `full_name` son calculados (appends del modelo), no se almacenan.

## Estructura de Datos

**POST /api/patients — Request:**
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "second_last_name": "López",
  "email": "juan@example.com",
  "phone": "555-1234",
  "mobile": "555-5678",
  "national_id": "NSS123456",
  "date_of_birth": "1985-03-15",
  "gender": "M",
  "address_line1": "Calle Principal 123",
  "city": "Ciudad de México",
  "state": "CDMX",
  "postal_code": "06600",
  "country": "México"
}
```

**GET /api/patients/find?search=Juan&gender=M — Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "full_name": "Juan Pérez López",
      "first_name": "Juan",
      "last_name": "Pérez",
      "email": "juan@example.com",
      "national_id": "NSS123456",
      "age": 41,
      "gender": "M",
      "is_active": true
    }
  ]
}
```

## Dependencias
- Permisos: `patient.view`, `patient.create`, `patient.update`.

## Estado de Desarrollo
⚠️ Parcial — Implementado: buscar, crear, actualizar. Pendiente: eliminar (soft delete).

## Pendientes (Roadmap)
- Endpoint DELETE `/api/patients/{id}` con soft delete.
- Paginación en resultados de búsqueda.
- Ordenamiento por columnas.
- Historial de consultas del paciente.
- Subida de documentos/imágenes.
- Múltiples direcciones por paciente.
