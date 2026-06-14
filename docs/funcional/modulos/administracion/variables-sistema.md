# Variables del Sistema — Documentación Funcional

## Propósito

Catálogo de variables disponibles para interpolar en campos de texto fijo (`fixed_text`) del builder de plantillas de informe. El frontend consume este catálogo para ofrecer autocompletado cuando el usuario escribe `{` dentro de un campo de texto fijo.

## Sintaxis de interpolación

```
{categoria.clave}
```

Ejemplos:
- `{paciente.nombre}` → «Juan Pérez»
- `{fecha.actual}` → «14/06/2026»
- `{medico.matricula}` → «MN-12345»

## Reglas de negocio

1. **Catálogo de solo lectura**: El endpoint devuelve una lista estática. No hay creación, edición ni eliminación de variables vía API.
2. **Categorías extensibles**: Nuevas categorías pueden agregarse sin modificar el frontend, ya que este agrupa por `category` automáticamente.
3. **Fallback del frontend**: Si el endpoint falla o no existe, el frontend tiene un catálogo mínimo hardcodeado con 14 variables base. No es bloqueante.
4. **Seguridad**: Requiere autenticación JWT (`auth.jwt`). No requiere permisos específicos.
5. **Claves no normalizadas**: Las claves (`key`) son identificadores semánticos, no referencias a columnas de base de datos. La resolución de valores reales ocurre en el motor de renderizado del informe.

## Categorías

| Categoría | Variables | Descripción |
|-----------|-----------|-------------|
| `paciente` | 15 | Datos demográficos y de contacto del paciente |
| `clinica` | 9 | Datos de la institución médica |
| `fecha` | 7 | Variantes de fecha y hora |
| `usuario` | 8 | Datos del profesional que genera el informe |
| `medico` | 10 | Datos del médico tratante |
| `informe` | 7 | Metadatos del informe en curso |
| **Total** | **56** | |

## Variables disponibles

### paciente
| Clave | Etiqueta |
|-------|----------|
| `nombre` | Nombre del paciente |
| `apellido` | Apellido del paciente |
| `nombre_completo` | Nombre completo |
| `edad` | Edad |
| `sexo` | Sexo |
| `nro_historia` | Nro. Historia Clínica |
| `dni` | DNI |
| `obra_social` | Obra social |
| `fecha_nacimiento` | Fecha de nacimiento |
| `direccion` | Dirección |
| `telefono` | Teléfono |
| `email` | Email |
| `peso` | Peso |
| `altura` | Altura |
| `grupo_sanguineo` | Grupo sanguíneo |

### clinica
| Clave | Etiqueta |
|-------|----------|
| `nombre` | Nombre de la clínica |
| `direccion` | Dirección |
| `telefono` | Teléfono |
| `email` | Email |
| `ciudad` | Ciudad |
| `provincia` | Provincia |
| `codigo_postal` | Código Postal |
| `web` | Sitio web |
| `logo` | Logo |

### fecha
| Clave | Etiqueta |
|-------|----------|
| `actual` | Fecha actual |
| `formato_largo` | Fecha formato largo |
| `corta` | Fecha corta |
| `hora` | Hora actual |
| `fecha_hora` | Fecha y hora |
| `anio` | Año actual |
| `mes` | Mes actual |

### usuario
| Clave | Etiqueta |
|-------|----------|
| `nombre` | Nombre del usuario |
| `apellido` | Apellido del usuario |
| `nombre_completo` | Nombre completo |
| `matricula` | Matrícula |
| `email` | Email |
| `especialidad` | Especialidad |
| `rol` | Rol |
| `telefono` | Teléfono |

### medico
| Clave | Etiqueta |
|-------|----------|
| `nombre` | Nombre del médico |
| `apellido` | Apellido del médico |
| `nombre_completo` | Nombre completo |
| `matricula` | Matrícula |
| `especialidad` | Especialidad |
| `email` | Email |
| `telefono` | Teléfono |
| `dias_consulta` | Días de consulta |
| `horario` | Horario |
| `nro_colegiado` | Nro. de colegiado |

### informe
| Clave | Etiqueta |
|-------|----------|
| `titulo` | Título del informe |
| `tipo` | Tipo de informe |
| `fecha_creacion` | Fecha de creación |
| `fecha_firma` | Fecha de firma |
| `pagina_actual` | Página actual |
| `pagina_total` | Total de páginas |
| `pagina_actual_de_total` | Página X de Y |

## Endpoint

| Método | Ruta |
|--------|------|
| GET | `/api/admin/system-variables` |

## Ejemplo de respuesta

```json
[
  {
    "category": "paciente",
    "key": "nombre",
    "label": "Nombre del paciente",
    "description": "Nombre completo del paciente"
  },
  {
    "category": "fecha",
    "key": "actual",
    "label": "Fecha actual",
    "description": "Fecha del día de hoy (formato dd/mm/aaaa)"
  }
]
```
