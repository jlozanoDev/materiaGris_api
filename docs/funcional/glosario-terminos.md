# Glosario de Términos — MateriaGris API

| Término | Definición |
|---------|------------|
| **Paciente** | Persona registrada en el sistema que recibe atención médica. Almacena datos demográficos, de contacto e información clínica básica. |
| **Usuario del sistema** | Persona que opera el sistema (médico, administrador). Tiene credenciales de acceso y permisos asignados. |
| **Consulta** | Evento clínico donde un médico atiende a un paciente. *(Módulo planificado)* |
| **Receta** | Prescripción médica emitida durante una consulta. *(Módulo planificado)* |
| **Orden de laboratorio** | Solicitud de estudios de laboratorio asociada a una consulta. *(Módulo planificado)* |
| **Orden de imagenología** | Solicitud de estudios de imagen asociada a una consulta. *(Módulo planificado)* |
| **Rol** | Conjunto de permisos agrupados bajo un nombre (ej. `admin`, `medico`). Un usuario puede tener múltiples roles. |
| **Permiso** | Acción atómica que un usuario puede realizar en el sistema (ej. `patient.create`, `admin.user.delete`). |
| **Grant (+1)** | Concesión explícita de un permiso. |
| **Deny (-1)** | Denegación explícita de un permiso. Tiene prioridad sobre Grant. |
| **Override** | Permiso asignado directamente a un usuario por encima de los permisos de su rol. |
| **RBAC** | Role-Based Access Control — modelo de control de acceso basado en roles. |
| **JWT** | JSON Web Token — estándar para tokens de autenticación stateless. |
| **Refresh Token** | Token de larga duración usado para obtener nuevos JWT sin requerir credenciales. |
| **Soft Delete** | Eliminación lógica: el registro no se borra físicamente sino que se marca con un timestamp `deleted_at`. |
| **NSS** | Número de Seguridad Social (u otro identificador nacional único del paciente). |
| **Auditoría** | Registro inmutable de eventos relevantes del sistema (inicios de sesión, cambios de permisos, accesos denegados). |
| **Informe** | Documento clínico digital generado a partir de una plantilla. Contiene los datos estructurados de una consulta o episodio médico. Sigue un ciclo de vida: borrador → firmado → cerrado. |
| **Plantilla de Informe** | Definición reutilizable de la estructura de un informe. Organizada en secciones, filas, columnas y campos con tipos específicos (texto, número, fecha, selección, etc.). |
| **Firma de Informe** | Acción de firmar electrónicamente un informe mediante imagen PNG capturada del dispositivo. Bloquea el contenido del informe y cambia su estado a `signed`. |
| **Cierre de Informe** | Acción de cerrar un informe firmado, que genera automáticamente el PDF final y cambia su estado a `closed`. Una vez cerrado, solo está disponible para descarga. |
| **Campo de Plantilla** | Unidad mínima de una plantilla de informe. Cada campo tiene un tipo, etiqueta, validaciones y opcionalmente una descripción semántica (`ai_help_description`) para la extracción con IA. |
| **Variable de Sistema** | Valor dinámico que se resuelve en tiempo de renderizado del informe, delimitado por `{...}`. Ejemplos: `{paciente.nombre}`, `{medico.matricula}`, `{fecha.actual}`. |
