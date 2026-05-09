# Flujo de Gestión de Pacientes — API

## Búsqueda de Pacientes

1. El frontend envía `GET /api/patients/find?search=Juan&gender=M&is_active=true`.
2. La API busca pacientes que coincidan con los criterios (nombre, NSS, email, teléfono, género, estado activo).
3. Responde con `200` y un array de pacientes con datos básicos (nombre completo, email, NSS, edad, género).

## Creación de Paciente

1. El frontend envía `POST /api/patients` con los datos del paciente en el cuerpo JSON.
2. La API valida:
   - Campos requeridos (nombre, al menos un apellido).
   - Unicidad de NSS (si se proporciona).
   - Unicidad de email (si se proporciona).
   - Formato de email válido.
3. Si la validación falla, responde con `422 Unprocessable Entity` y errores de validación.
4. Si la validación es exitosa, crea el paciente y responde con `201 Created` y los datos del paciente creado.

## Actualización de Paciente

1. El frontend envía `PUT /api/patients/{id}` con los campos a actualizar.
2. La API valida los mismos criterios que en creación (unicidad de NSS/email excluyendo el ID actual).
3. Si el paciente no existe, responde `404 Not Found`.
4. Si la validación falla, responde `422`.
5. Si es exitosa, actualiza y responde con `200` y los datos actualizados.

## Flujo de Error — Permiso Denegado

1. Un usuario sin permiso `patient.view` intenta buscar pacientes.
2. Middleware `RequirePermissions` detecta la falta de permiso.
3. Registra evento `policy.denied` en auditoría.
4. Responde con `401 Unauthorized`.

## Flujo de Error — Paciente No Encontrado

1. El frontend envía `PUT /api/patients/9999`.
2. La API no encuentra el paciente.
3. Responde con `404 Not Found`.
