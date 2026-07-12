# Exploration: Creación de README.md para MateriaGris API

## Current State

El repositorio ya tiene un `README.md` básico con servicios Docker, requisitos, comandos habituales, variables de entorno y endpoints principales (solo auth). Sin embargo, le falta la estructura completa que sí tiene el frontend: stack tecnológico detallado, estructura del proyecto, funcionalidades principales con tabla de endpoints, credenciales de prueba, tabla de comandos, y sección de documentación.

La API está completamente implementada con:
- **36 endpoints distribuidos en 8 módulos funcionales**
- **Arquitectura hexagonal** (Actions → Commands → Repositories/Services)
- **JWT custom** con `lcobucci/jwt` (access token en JSON body, refresh token en cookie httpOnly)
- **RBAC** con roles Admin, Professional (Médico), y permisos granulares
- **3 seeders de usuarios de prueba** (admin + professional)

## Affected Areas

- `README.md` (existente, se reescribirá completamente) — archivo principal a crear/actualizar
- `docs/INDICE.md` — referencia cruzada existente que debe mantenerse actualizada
- No se modifica código de aplicación, solo documentación en raíz

## Approaches

1. **Reescribir README.md completo** — Reemplazar el contenido actual con una estructura espejo del frontend
   - Pros: Coherencia visual entre ambos repos, cubre todo lo necesario
   - Cons: Hay que mantener la info actual del README existente que sigue siendo útil
   - Effort: Low

## Recommendation

Escribir un README completo que refleje la estructura del frontend, incorporando la información útil del README actual y toda la investigación realizada. La estructura propuesta:

1. **Título + descripción** (features principales con bullets)
2. **Stack tecnológico** (tabla con versiones de composer.json + docker)
3. **Requisitos previos** (Docker, .env, frontend repo)
4. **Instalación y ejecución** (pasos docker-compose + comandos artisan)
5. **Variables de entorno** (tabla con las más importantes: JWT, DB, CORS, LLM, STT)
6. **Estructura del proyecto** (árbol de directorios + tabla de capas hexagonales)
7. **Funcionalidades principales** (tabla módulo/rutas/roles RBAC — 8 módulos)
8. **Credenciales de prueba** (tabla email/password/rol de los 3 seeders)
9. **Comandos** (tabla con docker exec + composer scripts + artisan)
10. **Documentación** (enlace al INDICE + estructura docs/)

## Risks

- El ProfessionalUserSeeder tiene un typo en el dominio del email: `testprofesional@materiagis.local` (falta la 'r' en "materiagris"). El README del frontend ya lo documenta así, así que lo mantendremos consistente.
- No hay seeder para el rol "Recepcionista" — mencionarlo como pendiente o no incluirlo en credenciales.
- La URL de producción en Railway no está documentada explícitamente en el repo de API (solo en el frontend). Hay que verificarla o mencionar que se despliega en Railway.

## Ready for Proposal

Sí. Toda la información necesaria está reunida, no hay gaps que impidan escribir el README. La estructura y contenido están claros.
