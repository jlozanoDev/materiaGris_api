# Documentación Técnica — MateriaGris API

> Arquitectura, endpoints, servicios, base de datos y despliegue.
> Para documentación funcional ir a [`docs/funcional/`](../funcional/INDICE.md).

---

## Generales

| Archivo | Descripción |
|---------|-------------|
| [`arquitectura.md`](./arquitectura.md) | Arquitectura hexagonal: 5 capas, flujo de datos, DI, estructura de directorios |
| [`modelo-permisos-roles.md`](./modelo-permisos-roles.md) | Modelo RBAC: grant +1/-1, overrides, deny priority, caché, auditoría |
| [`estructura-base-datos.md`](./estructura-base-datos.md) | Esquema completo de base de datos: tablas, columnas, relaciones, migraciones |
| [`guia-endpoints-api.md`](./guia-endpoints-api.md) | Catálogo completo de endpoints: método, URI, middleware, permisos |
| [`auditoria-gaps-criticos.md`](./auditoria-gaps-criticos.md) | Gaps críticos detectados: endpoints faltantes, deuda técnica, tests |
| [`prompt-ia-documentacion-tecnica.md`](./prompt-ia-documentacion-tecnica.md) | Prompt para IA: auditar y generar documentación técnica |
| [`consultas-module-proposal.md`](./consultas-module-proposal.md) | Propuesta de diseño para el módulo de consultas médicas |

## Módulos

| Módulo | Archivos |
|--------|----------|
| **Autenticación** | [`modules/auth/modulo-autenticacion.md`](./modules/auth/modulo-autenticacion.md) |
| **Pacientes** | [`modules/patients/modulo-pacientes.md`](./modules/patients/modulo-pacientes.md) |
| **Admin — General** | [`modules/admin/panel-administracion.md`](./modules/admin/panel-administracion.md) |
| **Admin — Usuarios** | [`modules/admin/usuarios-crud.md`](./modules/admin/usuarios-crud.md) |
| **Admin — Roles** | [`modules/admin/roles-crud.md`](./modules/admin/roles-crud.md) |
| **Admin — Permisos** | [`modules/admin/permisos-crud.md`](./modules/admin/permisos-crud.md) |
| **Reports — Dictado y Autocompletado** | [`modules/reports/modulo-dictado-autocompletado.md`](./modules/reports/modulo-dictado-autocompletado.md) |
| **Reports — CRUD** | [`modules/reports/modulo-informes.md`](./modules/reports/modulo-informes.md) |
| **Admin — Plantillas de Informe** | [`modules/reports/modulo-plantillas.md`](./modules/reports/modulo-plantillas.md) |

## Referencia cruzada con funcional

| Módulo técnico | Docs técnicas | Docs funcionales |
|----------------|---------------|------------------|
| auth | `tecnica/modules/auth/` | [`funcional/modulos/autenticacion.md`](../funcional/modulos/autenticacion.md) |
| patients | `tecnica/modules/patients/` | [`funcional/modulos/pacientes.md`](../funcional/modulos/pacientes.md) |
| admin — users | `tecnica/modules/admin/usuarios-crud.md` | [`funcional/modulos/administracion/usuarios.md`](../funcional/modulos/administracion/usuarios.md) |
| admin — roles | `tecnica/modules/admin/roles-crud.md` | [`funcional/modulos/administracion/roles.md`](../funcional/modulos/administracion/roles.md) |
| admin — permissions | `tecnica/modules/admin/permisos-crud.md` | [`funcional/modulos/administracion/permisos.md`](../funcional/modulos/administracion/permisos.md) |
| admin — system-variables | `tecnica/modules/admin/panel-administracion.md` | [`funcional/modulos/administracion/variables-sistema.md`](../funcional/modulos/administracion/variables-sistema.md) |
| reports — dictado y autocompletado | `tecnica/modules/reports/modulo-dictado-autocompletado.md` | [`funcional/modulos/dictado-autocompletado.md`](../funcional/modulos/dictado-autocompletado.md) |
| reports — CRUD | `tecnica/modules/reports/modulo-informes.md` | [`funcional/modulos/informes.md`](../funcional/modulos/informes.md) |
| admin — report templates | `tecnica/modules/reports/modulo-plantillas.md` | [`funcional/modulos/plantillas-informes.md`](../funcional/modulos/plantillas-informes.md) |
