# Proposal: README.md completo para MateriaGris API

## Intent

La API de MateriaGris carece de un README.md completo. El actual (90 líneas) cubre solo servicios Docker y auth. El frontend tiene un README de 10 secciones bien estructurado. Este cambio crea un README.md API igual de completo, en español, siguiendo el mismo formato.

## Scope

### In Scope
- `README.md` en raíz con 10 secciones: descripción general, stack (tabla), requisitos previos, instalación/ejecución (bash), variables de entorno (tabla), estructura del proyecto (árbol + tabla de capas), funcionalidades/endpoints (tabla módulo/rutas/roles), credenciales de prueba (tabla), comandos (tabla), documentación (enlaces)
- Contenido en español, neutro/profesional
- Mismas credenciales que frontend: `test@materiagris.local` / `secret123` (Admin) y `testprofesional@materiagris.local` / `secret123` (Médico)
- Reemplazo completo del README.md existente

### Out of Scope
- Cambios de código funcional, migraciones, rutas, controladores
- Documentación del frontend
- Traducción a otros idiomas
- Modificación de `docs/` existentes

## Capabilities

### New Capabilities
None — puro artefacto de documentación, sin cambios de código.

### Modified Capabilities
None — ningún spec-level behavior cambia.

## Approach

1. **Plantilla estructural**: usar las 10 secciones del [README del frontend](https://github.com/jlozanoDev/MateriaGris_front) como molde exacto
2. **Contenido API**: cada sección se llena con datos del backend — stack PHP/Laravel, rutas API en lugar de rutas Vue, estructura hexagonal en lugar de capas Clean Architecture frontend
3. **Consistencia**: mismo formato de credenciales, mismas convenciones de tablas, mismo estilo de código bash
4. **Endpoint catalog**: tabla de módulos con rutas API (`GET/POST/PUT/DELETE`) y roles RBAC asociados

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `README.md` (raíz) | Replaced | README actual de 90 líneas → nuevo de ~10 secciones |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| README demasiado largo para la raíz | Low | Seguir concisión del frontend; extraer detalles a `docs/` |
| Desincronización con `docs/` existentes | Low | Enlazar `docs/INDICE.md` como fuente canónica; no duplicar contenido detallado |
| Typo en credenciales de prueba | Low | Usar exactamente los mismos valores que el frontend (incluyendo `materiagris.local` sin 's') |

## Rollback Plan

Revertir el commit que reemplaza `README.md`. El README anterior está en git history. Sin migraciones ni código — rollback instantáneo.

## Dependencies

- `docs/INDICE.md` y `docs/` deben existir para enlazar correctamente
- Acceso de lectura al README del frontend como referencia de formato

## Success Criteria

- [ ] README.md contiene las 10 secciones del formato frontend
- [ ] Sección de stack incluye PHP 8.2, Laravel 12, lcobucci/jwt, MySQL, Redis, Docker
- [ ] Sección de instalación reproduce los 4 comandos exactos de setup local
- [ ] Tabla de funcionalidades cubre los 8 módulos con 36 endpoints y roles RBAC
- [ ] Credenciales de prueba son idénticas a las del frontend
- [ ] README es autocontenido para un nuevo desarrollador que quiera levantar la API
