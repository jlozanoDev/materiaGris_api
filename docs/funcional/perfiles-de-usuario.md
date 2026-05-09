# Perfiles de Usuario — MateriaGris API

## Médico

- **Objetivo:** Gestionar pacientes del día a día, registrar consultas y actualizar expedientes.
- **Acceso principal:** Login → Dashboard → Módulo de Pacientes.
- **Funcionalidades clave:**
  - Buscar pacientes por múltiples criterios.
  - Crear nuevos pacientes.
  - Actualizar datos de pacientes existentes.
- **Permisos asignados:** `patient.view`, `patient.create`, `patient.update`.
- **Flujo típico:**
  1. Inicia sesión con email y contraseña.
  2. El sistema devuelve un JWT que se envía en cada request.
  3. Busca pacientes por nombre/NSS/teléfono.
  4. Selecciona un paciente y visualiza sus datos.
  5. Actualiza información del paciente según la consulta.

## Administrador

- **Objetivo:** Gestionar usuarios del sistema, roles y permisos.
- **Acceso principal:** Login → Panel de Administración.
- **Funcionalidades clave:**
  - CRUD completo de usuarios del sistema.
  - CRUD completo de roles y asignación de permisos.
  - Visualización del catálogo de permisos.
- **Permisos asignados:** `admin.user.*`, `admin.role.*`, `admin.permission.view`.
- **Flujo típico:**
  1. Inicia sesión como administrador.
  2. Accede al panel de administración.
  3. Crea un nuevo usuario asignándole roles y overrides de permisos.
  4. Modifica roles existentes, agregando o quitando permisos con grant/deny.
  5. Visualiza el catálogo completo de permisos del sistema.

## Recepcionista (futuro)

- **Objetivo:** Registrar pacientes y gestionar agenda de citas.
- **Acceso principal:** Login → Módulo de Pacientes / Agenda.
- **Funcionalidades clave (planificadas):**
  - Registro rápido de pacientes.
  - Búsqueda de pacientes.
  - Gestión de agenda de citas.
- **Permisos planificados:** `patient.view`, `patient.create`.
- **Nota:** Perfil aún no implementado en el sistema.
