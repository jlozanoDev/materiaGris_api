# Prompt Maestro: Auditoría y Reorganización de Documentación Funcional — API

Este documento contiene el prompt optimizado para que una IA analice, estructure y complete la documentación funcional de la API del proyecto MateriaGris.

---

## El Prompt

Copia y pega el siguiente texto en el chat de la IA:

> **Actúa como un Product Manager y Analista Funcional Senior. Tu objetivo es realizar una auditoría completa, reorganización y expansión de la documentación funcional de la API de mi proyecto.**
>
> ### 1. Contexto del Proyecto
> * **Nombre del Proyecto:** MateriaGris
> * **Sector:** Salud / Gestión de Pacientes
> * **Tipo:** API REST (Laravel 12) con Arquitectura Hexagonal
> * **Ubicación de la documentación funcional:** `docs/funcional/`
> * **Ubicación de la documentación técnica existente:** `docs/tecnica/`
>
> ### 2. Fase de Análisis
> * Busca y lee toda la documentación funcional existente en `docs/funcional/` (si la hay).
> * Lee la documentación técnica en `docs/tecnica/` para extraer reglas de negocio implícitas.
> * Examina el código fuente en `routes/api.php` para identificar todos los endpoints agrupados por módulo.
> * Examina `app/Http/Actions/`, `app/Commands/`, `app/Repositories/` y `app/Models/` para entender la lógica de negocio de cada módulo.
> * Identifica los **perfiles de usuario** (médico, administrador, recepcionista) y sus permisos asociados.
> * Identifica todas las **funcionalidades** expuestas vía API y las planificadas.
> * Detecta incoherencias entre la documentación existente y el código real.
>
> ### 3. Tarea de Reorganización (Arquitectura de Información)
> * Propón una estructura de documentación organizada por **Módulos Funcionales** (ej. `Autenticación`, `Gestión de Pacientes`, `Administración del Sistema`).
> * Clasifica los documentos existentes dentro de esta nueva jerarquía.
> * La estructura objetivo debe ser:
>   ```
>   docs/funcional/
>   ├── INDICE.md
>   ├── vision-general.md
>   ├── perfiles-de-usuario.md
>   ├── glosario-terminos.md
>   ├── prompt-ia-documentacion-funcional.md
>   ├── modulos/
>   │   ├── autenticacion.md
>   │   ├── pacientes.md
>   │   └── administracion/
>   │       ├── usuarios.md
>   │       ├── roles.md
>   │       └── permisos.md
>   └── flujos/
>       ├── autenticacion.md
>       ├── gestion-pacientes.md
>       └── administracion-sistema.md
>   ```
>
> ### 4. Generación de Contenido (Nuevos Archivos)
> Para cada módulo o flujo identificado que NO tenga documentación funcional detallada, genera el contenido de un nuevo archivo `.md` con la siguiente estructura:
> * **Propósito de Negocio:** Problema que resuelve y objetivo.
> * **Actores:** Roles de usuario que interactúan (médico, administrador, recepcionista).
> * **Funcionalidades:** Lista de endpoints y operaciones que ofrece, con método HTTP y URI.
> * **Criterios de Aceptación:** Condiciones que debe cumplir para considerarse completa.
> * **Reglas de Negocio:** Validaciones, restricciones (unicidad, soft delete, permisos) y lógica de dominio.
> * **Flujo Principal:** Secuencia de llamadas API que realiza el frontend para completar la tarea.
> * **Flujos Alternativos:** Casos de error, excepciones (credenciales inválidas, permisos denegados, duplicados).
> * **Estructura de Datos:** Formato JSON esperado en request/response para los endpoints clave.
> * **Dependencias:** De qué otros módulos o servicios depende.
> * **Estado de Desarrollo:** (Implementado / Parcial / Planificado).
> * **Pendientes (Roadmap):** Lista detallada de lo que falta para considerar el módulo completo.
>
> ### 5. Entregable Esperado
> 1. Un **Índice Funcional** (`docs/funcional/INDICE.md`) actualizado con la nueva estructura.
> 2. El contenido íntegro de los nuevos archivos Markdown para cada módulo y flujo faltante.
> 3. Un **Mapa de Cobertura** que muestre qué funcionalidades están documentadas vs implementadas vs planificadas.
> 4. Un resumen de **Gaps Funcionales** detectados (funcionalidades de negocio no cubiertas por la API).

---

## Instrucciones de Uso

1. **Antes de enviar**, asegúrate de que la IA tenga acceso a los archivos en `docs/`, `routes/api.php`, `app/Http/Actions/`, `app/Commands/` y `app/Models/`.
2. **Iteración:** Si el proyecto es muy extenso, puedes acotar: *"Empecemos solo con el Módulo de Autenticación"*.
3. **Actualización continua:** Ejecutar cada vez que se complete un hito de desarrollo.
