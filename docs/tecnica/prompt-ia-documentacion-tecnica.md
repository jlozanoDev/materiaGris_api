# Prompt Maestro: Auditoría y Reorganización de Documentación Técnica — API

Este documento contiene el prompt optimizado para que una IA analice, estructure y complete la documentación técnica de la API del proyecto MateriaGris.

---

## El Prompt

Copia y pega el siguiente texto en el chat de la IA:

> **Actúa como un Arquitecto de Software Senior y Tech Lead especializado en APIs REST. Tu objetivo es realizar una auditoría completa, reorganización y expansión de la documentación técnica de la API de mi proyecto.**
>
> ### 1. Contexto del Proyecto
> * **Nombre del Proyecto:** MateriaGris
> * **Framework:** Laravel 12 (PHP 8.2+)
> * **Arquitectura:** Hexagonal (Ports & Adapters): Route → Action (HTTP Adapter) → Command (Use Case) → Repository → Model
> * **Base de datos:** SQLite (dev), MySQL 8.0 (prod via Docker)
> * **Autenticación:** JWT custom con `lcobucci/jwt`
> * **RBAC:** Custom con grant (+1)/deny (-1), overrides, tabla de auditoría
> * **Infraestructura:** Docker Compose (app PHP-FPM, nginx, MySQL, Redis, Mailhog)
> * **Ubicación de la documentación técnica:** `docs/tecnica/`
>
> ### 2. Fase de Análisis
> * Lee toda la documentación técnica existente en `docs/tecnica/` y en la raíz `docs/`.
> * Examina `routes/api.php` para catalogar todos los endpoints agrupados por módulo.
> * Examina la estructura de `app/` completa:
>   - `app/Http/Actions/` — adaptadores HTTP (controllers)
>   - `app/Commands/` — casos de uso / lógica de aplicación
>   - `app/Repositories/` — acceso a datos
>   - `app/Models/` — modelos Eloquent
>   - `app/Services/` — servicios de dominio (JWT, permisos, auditoría)
>   - `app/Http/Middleware/` — middleware (AuthenticateJwt, RequirePermissions)
> * Examina `database/migrations/` para entender el esquema completo.
> * Examina `config/` para configuración relevante (jwt.php, cors.php, auth.php).
> * Detecta incoherencias entre lo documentado y el código real.
>
> ### 3. Tarea de Reorganización (Arquitectura de Información)
> * Propón una estructura de documentación organizada por **Módulos Técnicos**.
> * Clasifica los documentos existentes dentro de esta nueva jerarquía.
> * La estructura objetivo debe ser:
>   ```
>   docs/tecnica/
>   ├── INDICE.md
>   ├── arquitectura.md
>   ├── modelo-permisos-roles.md
>   ├── estructura-base-datos.md
>   ├── guia-endpoints-api.md
>   ├── auditoria-gaps-criticos.md
>   ├── prompt-ia-documentacion-tecnica.md
>   ├── consultas-module-proposal.md
>   └── modules/
>       ├── auth/
>       │   └── modulo-autenticacion.md
>       ├── patients/
>       │   └── modulo-pacientes.md
>       └── admin/
>           ├── panel-administracion.md
>           ├── usuarios-crud.md
>           ├── roles-crud.md
>           └── permisos-crud.md
>   ```
>
> ### 4. Generación de Contenido (Nuevos Archivos)
> Para cada módulo identificado que NO tenga documentación técnica detallada, genera el contenido de un nuevo archivo `.md` con la siguiente estructura:
>
> **Para `arquitectura.md`:**
> * Diagrama de capas (hexagonal) y flujo de datos.
> * Estructura de directorios de `app/`.
> * Flujo típico de request: HTTP → Middleware → Action → Command → Repository → Model → Response.
> * Inyección de dependencias (AppServiceProvider).
> * Mapa de módulos vs capas.
>
> **Para `modelo-permisos-roles.md`:**
> * Esquema de tablas: `roles`, `permissions`, `role_permissions`, `user_roles`, `user_permissions`, `user_effective_permissions`.
> * Regla deny-override (deny > grant).
> * Origen de overrides (`role|direct`).
> * Mecanismo de caché con `permissions_version`.
> * Middleware `RequirePermissions`.
> * Contrato del endpoint `GET /api/auth/me`.
> * Servicios: `PermissionService`, `AuditService`, `RoleAssignmentService`.
>
> **Para `estructura-base-datos.md`:**
> * Listado completo de tablas con columnas, tipos, constraints, índices y FKs.
> * Migraciones y seeders relevantes.
> * Relaciones entre entidades.
>
> **Para `guia-endpoints-api.md`:**
> * Tabla completa de todos los endpoints: método, URI, middleware, permiso requerido, action class.
> * Organizado por módulo (Health, Auth, Admin, Patients).
>
> **Para cada módulo en `modules/`:**
> * **Rutas:** Lista de endpoints del módulo.
> * **Action:** Clase HTTP Adapter, método que implementa, request validation.
> * **Commands (Use Cases):** Lista de casos de uso, qué hace cada uno.
> * **Repositories:** Interfaces/implementaciones, métodos principales.
> * **Models:** Entidades involucradas, atributos, relaciones.
> * **Middleware:** Middleware aplicado (auth.jwt, require_permissions).
> * **Flujo de datos:** Secuencia detallada de principio a fin para operaciones clave.
> * **Formato de respuesta:** Ejemplos JSON de request/response.
> * **Códigos de error:** Posibles respuestas HTTP y sus significados.
> * **Estado de Desarrollo:** (Completo / Parcial / Planificado).
> * **Pendientes (Roadmap):** Lo que falta: endpoints faltantes, tests, refactors, validaciones.
>
> ### 5. Entregable Esperado
> 1. Un **Índice Técnico** (`docs/tecnica/INDICE.md`) actualizado con la nueva estructura y referencias cruzadas a la documentación funcional.
> 2. El contenido íntegro de todos los archivos Markdown nuevos y refactorizados.
> 3. Un **Mapa de Cobertura** que muestre qué módulos están documentados vs implementados.
> 4. Un resumen de **Gaps Críticos** detectados: bugs, deuda técnica, endpoints faltantes, tests ausentes.

---

## Instrucciones de Uso

1. **Antes de enviar**, asegúrate de que la IA tenga acceso a todo el árbol `app/`, `routes/`, `database/migrations/`, `config/` y los docs existentes.
2. **Refactorización:** Los archivos existentes (`database-structure.md`, `permissions.md`, `RBAC-Audit.md`, `RBAC-Audit-Prompt.md`, `consultations-module.md`) deben ser refactorizados e integrados en la nueva estructura. El prompt original `RBAC-Audit-Prompt.md` puede descartarse ya que el RBAC está implementado.
3. **Iteración:** Puedes acotar a un módulo específico si lo prefieres.
4. **Actualización continua:** Ejecutar cada vez que se agreguen nuevos endpoints o módulos.
