# Documentación Funcional — MateriaGris API

> Propósito de negocio, funcionalidades, reglas de negocio, flujos de API y glosario.
> Para documentación técnica ir a [`docs/tecnica/`](../tecnica/INDICE.md).

---

## Transversales

| Archivo | Descripción |
|---------|-------------|
| [`vision-general.md`](./vision-general.md) | ¿Qué es MateriaGris? Propósito, objetivos y alcance del sistema |
| [`perfiles-de-usuario.md`](./perfiles-de-usuario.md) | Actores del sistema: médico, administrador, recepcionista |
| [`glosario-terminos.md`](./glosario-terminos.md) | Definiciones de términos de negocio (paciente, consulta, rol, permiso…) |
| [`prompt-ia-documentacion-funcional.md`](./prompt-ia-documentacion-funcional.md) | Prompt para IA: auditar y generar documentación funcional |

## Módulos funcionales

| Módulo | Archivo | ¿Qué cubre? |
|--------|---------|-------------|
| Autenticación | [`modulos/autenticacion.md`](./modulos/autenticacion.md) | Login, logout, refresh, recuperación de contraseña |
| Pacientes | [`modulos/pacientes.md`](./modulos/pacientes.md) | Alta, búsqueda, edición de pacientes |
| Admin — Usuarios | [`modulos/administracion/usuarios.md`](./modulos/administracion/usuarios.md) | CRUD de usuarios del sistema |
| Admin — Roles | [`modulos/administracion/roles.md`](./modulos/administracion/roles.md) | Roles y asignación de permisos |
| Admin — Permisos | [`modulos/administracion/permisos.md`](./modulos/administracion/permisos.md) | Catálogo de permisos del sistema |
| Admin — Variables del Sistema | [`modulos/administracion/variables-sistema.md`](./modulos/administracion/variables-sistema.md) | Variables para autocompletado en plantillas de informe |

## Flujos de API

| Flujo | Archivo | Descripción |
|-------|---------|-------------|
| Autenticación | [`flujos/autenticacion.md`](./flujos/autenticacion.md) | Login, refresh, logout, recuperación de contraseña |
| Gestión de pacientes | [`flujos/gestion-pacientes.md`](./flujos/gestion-pacientes.md) | Buscar, crear y editar pacientes vía API |
| Administración del sistema | [`flujos/administracion-sistema.md`](./flujos/administracion-sistema.md) | CRUD de usuarios, roles y permisos vía API |

## Estado de cobertura

| Funcionalidad | Docs | Implementación | Prioridad |
|---------------|------|----------------|-----------|
| Health check | ✅ Documentado | ✅ Completo | Baja |
| Autenticación | ✅ Documentado | ✅ Completo | Alta |
| Pacientes | ✅ Documentado | ⚠️ Parcial (falta DELETE) | Alta |
| Admin — Usuarios | ✅ Documentado | ✅ Completo | Alta |
| Admin — Roles | ✅ Documentado | ✅ Completo | Alta |
| Admin — Permisos | ✅ Documentado | ✅ Completo | Media |
| Admin — Variables del Sistema | ✅ Documentado | ✅ Completo | Media |
| Consultas médicas | ❌ Pendiente | ❌ No implementado | Futura |

✅ Documentado / Implementado · ⚠️ Parcial / En progreso · ❌ No existe / No implementado
