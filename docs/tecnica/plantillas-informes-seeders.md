# Plantillas de Informes — Datos Semilla para Backend Laravel

> **Propósito**: Este archivo contiene 3 plantillas de informe médico completas como estructuras JSON
> listas para ser utilizadas como seeders en el backend Laravel. Cada bloque JSON es un objeto
> `ReportTemplate` válido que puede enviarse vía `POST /admin/report-templates`.

---

## Instrucciones para el desarrollador backend

### Cómo usar este archivo

1. Cada bloque JSON representa una llamada a `POST /admin/report-templates` con el payload completo.
2. Los IDs usan placeholders descriptivos (`uuid-hcg-section-anamnesis`, etc.). **Reemplazar** cada
   UUID por `Str::uuid()->toString()` o `ramsey/uuid` en el seeder de Laravel.
3. Las claves (`key`) son únicas globalmente dentro de cada plantilla — no requieren modificación.
4. Cada plantilla incluye `ai_help_description` en todos los campos editables. Si el backend rechaza
   este campo, simplemente se filtra sin romper la estructura (el frontend lo envía, pero el backend
   puede ignorarlo en el JSON column).

### Notas importantes

- **`ai_help_description`**: Campo nuevo que el frontend serializa. El backend debe aceptarlo en el
  payload, o en su defecto ignorarlo (el `structure` es un JSON column, no hay migraciones).
- **UUIDs descriptivos**: Los IDs como `uuid-hcg-section-anamnesis` son placeholders. En el seeder
  final, reemplazar cada uno con UUIDs reales. El frontend usa `crypto.randomUUID()` que genera UUID
  v4; Laravel puede usar `Str::uuid()`.
- **Variables del sistema**: Las plantillas usan variables `{categoria.clave}` que el frontend
  interpola con datos reales del paciente/clínica/médico al renderizar el formulario.
- **showLabel**: Los `fixed_text` y separadores tienen `showLabel: false`. El resto usa el valor
  por defecto `true` (omitido del JSON para brevedad).
- **Column widths**: Distribución en grid de 12 (6+6 para dos columnas, 4+4+4 para tres, 12 para
  una). El número de columnas por row determina el ancho.

### Variables del sistema utilizadas

| Variable | Descripción |
|----------|-------------|
| `{paciente.nombre}` | Nombre completo del paciente |
| `{paciente.edad}` | Edad en años |
| `{paciente.sexo}` | Sexo del paciente |
| `{paciente.nro_historia}` | Número de historia clínica |
| `{clinica.nombre}` | Nombre de la clínica o institución |
| `{clinica.direccion}` | Dirección de la clínica |
| `{clinica.telefono}` | Teléfono de contacto |
| `{fecha.actual}` | Fecha del día de hoy |
| `{fecha.formato_largo}` | Fecha en formato largo (ej: 14 de junio de 2026) |
| `{medico.nombre}` | Nombre completo del médico tratante |
| `{medico.matricula}` | Número de matrícula del médico |
| `{medico.especialidad}` | Especialidad del médico |
| `{usuario.nombre}` | Nombre del profesional que genera el informe |
| `{usuario.matricula}` | Número de matrícula profesional |

---

## 1. Historia Clínica General (HCG)

```json
{
  "name": "Historia Clínica General",
  "description": "Plantilla completa para la confección de la Historia Clínica General. Incluye anamnesis con antecedentes personales y familiares, examen físico por sistemas, diagnóstico principal y plan terapéutico. Utilizada en consultas ambulatorias de todas las especialidades.",
  "structure": {
    "header": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-hcg-section-header",
          "label": "Encabezado",
          "display": "default",
          "rows": [
            {
              "id": "uuid-hcg-header-row-1",
              "columns": [
                {
                  "id": "uuid-hcg-header-col-1",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-header-clinica",
                      "key": "hcg_header_clinica",
                      "type": "fixed_text",
                      "label": "Clínica",
                      "required": false,
                      "showLabel": false,
                      "text_content": "{clinica.nombre}",
                      "styling_options": {
                        "bold": true,
                        "size": "lg"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-hcg-header-row-2",
              "columns": [
                {
                  "id": "uuid-hcg-header-col-2",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-header-titulo",
                      "key": "hcg_header_titulo",
                      "type": "fixed_text",
                      "label": "Título documento",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Historia Clínica General",
                      "styling_options": {
                        "bold": true,
                        "size": "md"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-hcg-header-row-3",
              "columns": [
                {
                  "id": "uuid-hcg-header-col-3",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-header-datos",
                      "key": "hcg_header_datos",
                      "type": "fixed_text",
                      "label": "Datos paciente",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Paciente: {paciente.nombre} | HC N°: {paciente.nro_historia} | Edad: {paciente.edad} | Sexo: {paciente.sexo} | Fecha: {fecha.formato_largo}"
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-hcg-header-row-4",
              "columns": [
                {
                  "id": "uuid-hcg-header-col-4",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-header-medico",
                      "key": "hcg_header_medico",
                      "type": "fixed_text",
                      "label": "Médico tratante",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Médico tratante: {medico.nombre} — Mat. {medico.matricula} | Especialidad: {medico.especialidad}"
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-hcg-header-row-5",
              "columns": [
                {
                  "id": "uuid-hcg-header-col-5",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-fecha-documento",
                      "key": "hcg_fecha_documento",
                      "type": "date",
                      "label": "Fecha del Documento",
                      "required": true,
                      "ai_help_description": "Fecha de realización de la historia clínica. Seleccionar la fecha en que se confecciona el documento."
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    },
    "sections": [
      {
        "id": "uuid-hcg-section-anamnesis",
        "label": "Anamnesis",
        "display": "default",
        "rows": [
          {
            "id": "uuid-hcg-row-anam-1",
            "columns": [
              {
                "id": "uuid-hcg-col-anam-1",
                "fields": [
                  {
                    "id": "uuid-hcg-field-motivo-consulta",
                    "key": "hcg_motivo_consulta",
                    "type": "text",
                    "label": "Motivo de Consulta",
                    "required": true,
                    "ai_help_description": "Razón principal por la que el paciente acude a consulta. Describir en una frase el síntoma o preocupación principal."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-anam-2",
            "columns": [
              {
                "id": "uuid-hcg-col-anam-2",
                "fields": [
                  {
                    "id": "uuid-hcg-field-enfermedad-actual",
                    "key": "hcg_enfermedad_actual",
                    "type": "textarea",
                    "label": "Enfermedad Actual",
                    "required": true,
                    "ai_help_description": "Descripción cronológica detallada de los síntomas actuales: fecha de inicio, modo de instauración, evolución, características, factores agravantes y atenuantes."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-anam-3",
            "columns": [
              {
                "id": "uuid-hcg-col-anam-3a",
                "fields": [
                  {
                    "id": "uuid-hcg-field-antec-personales",
                    "key": "hcg_antecedentes_personales",
                    "type": "textarea",
                    "label": "Antecedentes Personales",
                    "required": false,
                    "ai_help_description": "Enfermedades crónicas diagnosticadas, hospitalizaciones previas con fechas aproximadas, cirugías, traumatismos, transfusiones."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-anam-3b",
                "fields": [
                  {
                    "id": "uuid-hcg-field-antec-familiares",
                    "key": "hcg_antecedentes_familiares",
                    "type": "textarea",
                    "label": "Antecedentes Familiares",
                    "required": false,
                    "ai_help_description": "Enfermedades hereditarias o relevantes en familiares de primer grado (padres, hermanos, hijos)."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-anam-4",
            "columns": [
              {
                "id": "uuid-hcg-col-anam-4a",
                "fields": [
                  {
                    "id": "uuid-hcg-field-habitos",
                    "key": "hcg_habitos",
                    "type": "multi_select",
                    "label": "Hábitos",
                    "required": false,
                    "options": [
                      { "label": "Tabaco", "value": "tabaco" },
                      { "label": "Alcohol", "value": "alcohol" },
                      { "label": "Ejercicio", "value": "ejercicio" },
                      { "label": "Otros", "value": "otros" }
                    ],
                    "ai_help_description": "Hábitos del paciente. Seleccionar todos los que apliquen: tabaco, alcohol, ejercicio, otros."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-anam-4b",
                "fields": [
                  {
                    "id": "uuid-hcg-field-alergias",
                    "key": "hcg_alergias",
                    "type": "multi_select",
                    "label": "Alergias Conocidas",
                    "required": false,
                    "options": [
                      { "label": "Penicilina", "value": "penicilina" },
                      { "label": "Sulfamidas", "value": "sulfamidas" },
                      { "label": "AINEs", "value": "aines" },
                      { "label": "Látex", "value": "latex" },
                      { "label": "Alimentos", "value": "alimentos" },
                      { "label": "Otros", "value": "otros" }
                    ],
                    "ai_help_description": "Alergias conocidas del paciente. Seleccionar todas las que apliquen."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-anam-5",
            "columns": [
              {
                "id": "uuid-hcg-col-anam-5",
                "fields": [
                  {
                    "id": "uuid-hcg-field-medicacion-habitual",
                    "key": "hcg_medicacion_habitual",
                    "type": "dynamic_table",
                    "label": "Medicación Habitual",
                    "required": false,
                    "columns": [
                      {
                        "key": "medicamento",
                        "label": "Medicamento",
                        "type": "text",
                        "required": true
                      },
                      {
                        "key": "dosis",
                        "label": "Dosis",
                        "type": "text",
                        "required": true
                      },
                      {
                        "key": "frecuencia",
                        "label": "Frecuencia",
                        "type": "select",
                        "required": true,
                        "options": [
                          { "label": "c/8h", "value": "c8h" },
                          { "label": "c/12h", "value": "c12h" },
                          { "label": "c/24h", "value": "c24h" },
                          { "label": "S.O.S.", "value": "sos" }
                        ]
                      }
                    ],
                    "ai_help_description": "Tabla de medicación que el paciente toma habitualmente. Columnas: nombre del medicamento, dosis, frecuencia (c/8h, c/12h, c/24h, S.O.S.)."
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "uuid-hcg-section-examen-fisico",
        "label": "Examen Físico",
        "display": "default",
        "rows": [
          {
            "id": "uuid-hcg-row-ef-1",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-1a",
                "fields": [
                  {
                    "id": "uuid-hcg-field-ta",
                    "key": "hcg_ta",
                    "type": "text",
                    "label": "TA (Presión Arterial)",
                    "required": true,
                    "max_chars": 10,
                    "placeholder": "120/80",
                    "ai_help_description": "Presión arterial en formato sistólica/diastólica (ej: 120/80 mmHg)."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-ef-1b",
                "fields": [
                  {
                    "id": "uuid-hcg-field-fc",
                    "key": "hcg_fc",
                    "type": "number",
                    "label": "Frecuencia Cardíaca",
                    "required": true,
                    "min": 0,
                    "ai_help_description": "Frecuencia cardíaca en latidos por minuto (lpm). Valor normal adulto: 60-100 lpm."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-ef-1c",
                "fields": [
                  {
                    "id": "uuid-hcg-field-fr",
                    "key": "hcg_fr",
                    "type": "number",
                    "label": "Frecuencia Respiratoria",
                    "required": true,
                    "min": 0,
                    "ai_help_description": "Frecuencia respiratoria en respiraciones por minuto (rpm). Valor normal adulto: 12-20 rpm."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-ef-2",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-2a",
                "fields": [
                  {
                    "id": "uuid-hcg-field-temperatura",
                    "key": "hcg_temperatura",
                    "type": "number",
                    "label": "Temperatura",
                    "required": false,
                    "min": 30,
                    "max": 45,
                    "decimals": 1,
                    "ai_help_description": "Temperatura corporal en grados Celsius (°C). Valor normal: 36.0-37.5 °C."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-ef-2b",
                "fields": [
                  {
                    "id": "uuid-hcg-field-peso",
                    "key": "hcg_peso",
                    "type": "number",
                    "label": "Peso",
                    "required": false,
                    "min": 0,
                    "decimals": 1,
                    "ai_help_description": "Peso corporal en kilogramos (kg)."
                  }
                ]
              },
              {
                "id": "uuid-hcg-col-ef-2c",
                "fields": [
                  {
                    "id": "uuid-hcg-field-talla",
                    "key": "hcg_talla",
                    "type": "number",
                    "label": "Talla",
                    "required": false,
                    "min": 0,
                    "decimals": 2,
                    "ai_help_description": "Talla en metros (ej: 1.70)."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-ef-3",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-3",
                "fields": [
                  {
                    "id": "uuid-hcg-field-imc",
                    "key": "hcg_imc",
                    "type": "number",
                    "label": "IMC",
                    "required": false,
                    "min": 0,
                    "decimals": 1,
                    "ai_help_description": "Índice de Masa Corporal calculado como peso(kg) / talla²(m). Debe calcularse a partir de los campos peso y talla."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-ef-4",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-4",
                "fields": [
                  {
                    "id": "uuid-hcg-field-exploracion-general",
                    "key": "hcg_exploracion_general",
                    "type": "textarea",
                    "label": "Exploración General",
                    "required": false,
                    "ai_help_description": "Hallazgos del examen físico general: estado general, piel y mucosas, tejido celular subcutáneo, marcha, actitud."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-ef-5",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-5",
                "fields": [
                  {
                    "id": "uuid-hcg-field-separador-sistemas",
                    "key": "hcg_separador_sistemas",
                    "type": "horizontal_separator",
                    "label": "",
                    "required": false,
                    "showLabel": false
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-ef-6",
            "columns": [
              {
                "id": "uuid-hcg-col-ef-6",
                "fields": [
                  {
                    "id": "uuid-hcg-field-exploracion-sistemas",
                    "key": "hcg_exploracion_sistemas",
                    "type": "textarea",
                    "label": "Exploración por Sistemas",
                    "required": false,
                    "ai_help_description": "Hallazgos del examen físico por sistemas: cabeza y cuello, tórax, cardiovascular, abdomen, neurológico, osteoarticular."
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "uuid-hcg-section-diagnostico-plan",
        "label": "Diagnóstico y Plan",
        "display": "default",
        "rows": [
          {
            "id": "uuid-hcg-row-dp-1",
            "columns": [
              {
                "id": "uuid-hcg-col-dp-1",
                "fields": [
                  {
                    "id": "uuid-hcg-field-diagnostico-principal",
                    "key": "hcg_diagnostico_principal",
                    "type": "text",
                    "label": "Diagnóstico Principal",
                    "required": true,
                    "ai_help_description": "Diagnóstico presuntivo o definitivo principal, preferentemente codificado según CIE-10."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-dp-2",
            "columns": [
              {
                "id": "uuid-hcg-col-dp-2",
                "fields": [
                  {
                    "id": "uuid-hcg-field-diagnosticos-secundarios",
                    "key": "hcg_diagnosticos_secundarios",
                    "type": "textarea",
                    "label": "Diagnósticos Secundarios",
                    "required": false,
                    "ai_help_description": "Diagnósticos secundarios o comorbilidades relevantes, uno por línea."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-dp-3",
            "columns": [
              {
                "id": "uuid-hcg-col-dp-3",
                "fields": [
                  {
                    "id": "uuid-hcg-field-plan-estudios",
                    "key": "hcg_plan_estudios",
                    "type": "multi_select",
                    "label": "Estudios Solicitados",
                    "required": false,
                    "options": [
                      { "label": "Laboratorio", "value": "laboratorio" },
                      { "label": "Radiografía", "value": "radiografia" },
                      { "label": "Ecografía", "value": "ecografia" },
                      { "label": "Tomografía", "value": "tomografia" },
                      { "label": "Resonancia Magnética", "value": "resonancia" },
                      { "label": "Electrocardiograma", "value": "ecg" },
                      { "label": "Otros", "value": "otros" }
                    ],
                    "ai_help_description": "Estudios complementarios solicitados. Seleccionar todos los que apliquen."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-hcg-row-dp-4",
            "columns": [
              {
                "id": "uuid-hcg-col-dp-4",
                "fields": [
                  {
                    "id": "uuid-hcg-field-plan-terapeutico",
                    "key": "hcg_plan_terapeutico",
                    "type": "textarea",
                    "label": "Plan Terapéutico",
                    "required": true,
                    "ai_help_description": "Plan terapéutico: indicaciones farmacológicas (medicamento, dosis, duración), no farmacológicas, derivaciones a especialistas, y pautas de seguimiento."
                  }
                ]
              }
            ]
          }
        ]
      }
    ],
    "footer": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-hcg-section-footer",
          "label": "Pie",
          "display": "default",
          "rows": [
            {
              "id": "uuid-hcg-footer-row-1",
              "columns": [
                {
                  "id": "uuid-hcg-footer-col-1",
                  "fields": [
                    {
                      "id": "uuid-hcg-field-footer-firma",
                      "key": "hcg_footer_firma",
                      "type": "fixed_text",
                      "label": "Firma médico",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Documento generado el {fecha.actual}. {medico.nombre} — Mat. {medico.matricula}"
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

---

## 2. Informe de Alta (IA)

```json
{
  "name": "Informe de Alta",
  "description": "Plantilla para la confección del Informe de Alta hospitalaria. Incluye datos de ingreso, evolución clínica durante la internación, diagnóstico de egreso, medicación al alta, recomendaciones y controles posteriores. Utilizada al finalizar una internación en todos los servicios.",
  "structure": {
    "header": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-ia-section-header",
          "label": "Encabezado",
          "display": "default",
          "rows": [
            {
              "id": "uuid-ia-header-row-1",
              "columns": [
                {
                  "id": "uuid-ia-header-col-1",
                  "fields": [
                    {
                      "id": "uuid-ia-field-header-clinica",
                      "key": "ia_header_clinica",
                      "type": "fixed_text",
                      "label": "Clínica",
                      "required": false,
                      "showLabel": false,
                      "text_content": "{clinica.nombre}",
                      "styling_options": {
                        "bold": true,
                        "size": "lg"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ia-header-row-2",
              "columns": [
                {
                  "id": "uuid-ia-header-col-2",
                  "fields": [
                    {
                      "id": "uuid-ia-field-header-direccion",
                      "key": "ia_header_direccion",
                      "type": "fixed_text",
                      "label": "Dirección",
                      "required": false,
                      "showLabel": false,
                      "text_content": "{clinica.direccion} | Tel: {clinica.telefono}"
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ia-header-row-3",
              "columns": [
                {
                  "id": "uuid-ia-header-col-3",
                  "fields": [
                    {
                      "id": "uuid-ia-field-header-titulo",
                      "key": "ia_header_titulo",
                      "type": "fixed_text",
                      "label": "Título documento",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Informe de Alta Médica",
                      "styling_options": {
                        "bold": true,
                        "size": "md"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ia-header-row-4",
              "columns": [
                {
                  "id": "uuid-ia-header-col-4",
                  "fields": [
                    {
                      "id": "uuid-ia-field-header-paciente",
                      "key": "ia_header_paciente",
                      "type": "fixed_text",
                      "label": "Datos paciente",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Paciente: {paciente.nombre} | HC N°: {paciente.nro_historia}"
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    },
    "sections": [
      {
        "id": "uuid-ia-section-ingreso",
        "label": "Datos de Ingreso",
        "display": "default",
        "rows": [
          {
            "id": "uuid-ia-row-ing-1",
            "columns": [
              {
                "id": "uuid-ia-col-ing-1a",
                "fields": [
                  {
                    "id": "uuid-ia-field-fecha-ingreso",
                    "key": "ia_fecha_ingreso",
                    "type": "date",
                    "label": "Fecha de Ingreso",
                    "required": true,
                    "ai_help_description": "Fecha en que el paciente ingresó a la institución."
                  }
                ]
              },
              {
                "id": "uuid-ia-col-ing-1b",
                "fields": [
                  {
                    "id": "uuid-ia-field-fecha-egreso",
                    "key": "ia_fecha_egreso",
                    "type": "date",
                    "label": "Fecha de Egreso",
                    "required": false,
                    "ai_help_description": "Fecha en que el paciente egresó de la institución."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-ing-2",
            "columns": [
              {
                "id": "uuid-ia-col-ing-2",
                "fields": [
                  {
                    "id": "uuid-ia-field-diagnostico-ingreso",
                    "key": "ia_diagnostico_ingreso",
                    "type": "text",
                    "label": "Diagnóstico de Ingreso",
                    "required": true,
                    "ai_help_description": "Diagnóstico con el que el paciente fue internado, preferentemente codificado según CIE-10."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-ing-3",
            "columns": [
              {
                "id": "uuid-ia-col-ing-3a",
                "fields": [
                  {
                    "id": "uuid-ia-field-servicio",
                    "key": "ia_servicio",
                    "type": "select",
                    "label": "Servicio de Internación",
                    "required": true,
                    "options": [
                      { "label": "Clínica Médica", "value": "clinica_medica" },
                      { "label": "Cirugía", "value": "cirugia" },
                      { "label": "Pediatría", "value": "pediatria" },
                      { "label": "UTI", "value": "uti" },
                      { "label": "Obstetricia", "value": "obstetricia" }
                    ],
                    "ai_help_description": "Servicio hospitalario responsable de la internación."
                  }
                ]
              },
              {
                "id": "uuid-ia-col-ing-3b",
                "fields": [
                  {
                    "id": "uuid-ia-field-medico-tratante",
                    "key": "ia_medico_tratante",
                    "type": "text",
                    "label": "Médico Tratante",
                    "required": false,
                    "default_value": "{medico.nombre}",
                    "ai_help_description": "Nombre del médico tratante responsable de la internación."
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "uuid-ia-section-evolucion",
        "label": "Evolución",
        "display": "default",
        "rows": [
          {
            "id": "uuid-ia-row-evo-1",
            "columns": [
              {
                "id": "uuid-ia-col-evo-1",
                "fields": [
                  {
                    "id": "uuid-ia-field-resumen-evolucion",
                    "key": "ia_resumen_evolucion",
                    "type": "textarea",
                    "label": "Resumen de Evolución",
                    "required": true,
                    "ai_help_description": "Resumen cronológico de la evolución clínica del paciente durante la internación. Incluir eventos relevantes, respuesta al tratamiento y cambios en el estado clínico."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-evo-2",
            "columns": [
              {
                "id": "uuid-ia-col-evo-2",
                "fields": [
                  {
                    "id": "uuid-ia-field-procedimientos",
                    "key": "ia_procedimientos",
                    "type": "textarea",
                    "label": "Procedimientos Realizados",
                    "required": false,
                    "ai_help_description": "Procedimientos diagnósticos y/o terapéuticos realizados durante la internación, con fechas."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-evo-3",
            "columns": [
              {
                "id": "uuid-ia-col-evo-3a",
                "fields": [
                  {
                    "id": "uuid-ia-field-complicaciones",
                    "key": "ia_complicaciones",
                    "type": "textarea",
                    "label": "Complicaciones",
                    "required": false,
                    "ai_help_description": "Complicaciones ocurridas durante la internación. Si no las hubo, indicar 'Sin complicaciones'."
                  }
                ]
              },
              {
                "id": "uuid-ia-col-evo-3b",
                "fields": [
                  {
                    "id": "uuid-ia-field-interconsultas",
                    "key": "ia_interconsultas",
                    "type": "textarea",
                    "label": "Interconsultas",
                    "required": false,
                    "ai_help_description": "Interconsultas realizadas a otras especialidades durante la internación, con hallazgos relevantes."
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "uuid-ia-section-alta",
        "label": "Alta Médica",
        "display": "default",
        "rows": [
          {
            "id": "uuid-ia-row-alta-1",
            "columns": [
              {
                "id": "uuid-ia-col-alta-1",
                "fields": [
                  {
                    "id": "uuid-ia-field-diagnostico-egreso",
                    "key": "ia_diagnostico_egreso",
                    "type": "text",
                    "label": "Diagnóstico de Egreso",
                    "required": true,
                    "ai_help_description": "Diagnóstico principal al momento del alta hospitalaria, codificado según CIE-10."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-alta-2",
            "columns": [
              {
                "id": "uuid-ia-col-alta-2",
                "fields": [
                  {
                    "id": "uuid-ia-field-condicion-egreso",
                    "key": "ia_condicion_egreso",
                    "type": "select",
                    "label": "Condición de Egreso",
                    "required": true,
                    "options": [
                      { "label": "Mejorado", "value": "mejorado" },
                      { "label": "Curado", "value": "curado" },
                      { "label": "Derivado", "value": "derivado" },
                      { "label": "Fallecido", "value": "fallecido" }
                    ],
                    "ai_help_description": "Condición clínica del paciente al momento del egreso."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-alta-3",
            "columns": [
              {
                "id": "uuid-ia-col-alta-3",
                "fields": [
                  {
                    "id": "uuid-ia-field-medicacion-alta",
                    "key": "ia_medicacion_alta",
                    "type": "dynamic_table",
                    "label": "Medicación al Alta",
                    "required": true,
                    "columns": [
                      {
                        "key": "medicamento",
                        "label": "Medicamento",
                        "type": "text",
                        "required": true
                      },
                      {
                        "key": "dosis",
                        "label": "Dosis",
                        "type": "text",
                        "required": true
                      },
                      {
                        "key": "frecuencia",
                        "label": "Frecuencia",
                        "type": "select",
                        "required": true,
                        "options": [
                          { "label": "c/8h", "value": "c8h" },
                          { "label": "c/12h", "value": "c12h" },
                          { "label": "c/24h", "value": "c24h" },
                          { "label": "S.O.S.", "value": "sos" }
                        ]
                      },
                      {
                        "key": "duracion",
                        "label": "Duración (días)",
                        "type": "number",
                        "required": true
                      },
                      {
                        "key": "indicacion",
                        "label": "Indicación",
                        "type": "text",
                        "required": false
                      }
                    ],
                    "ai_help_description": "Tabla de medicamentos indicados al alta con dosis, frecuencia, duración en días e indicación."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-alta-4",
            "columns": [
              {
                "id": "uuid-ia-col-alta-4",
                "fields": [
                  {
                    "id": "uuid-ia-field-recomendaciones",
                    "key": "ia_recomendaciones",
                    "type": "textarea",
                    "label": "Recomendaciones",
                    "required": true,
                    "ai_help_description": "Recomendaciones y cuidados que el paciente debe seguir tras el alta: dieta, actividad física, cuidados de heridas, signos de alarma."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ia-row-alta-5",
            "columns": [
              {
                "id": "uuid-ia-col-alta-5a",
                "fields": [
                  {
                    "id": "uuid-ia-field-controles-posteriores",
                    "key": "ia_controles_posteriores",
                    "type": "textarea",
                    "label": "Controles Posteriores",
                    "required": false,
                    "ai_help_description": "Indicaciones para controles ambulatorios posteriores al alta: especialidad, plazos, estudios a realizar."
                  }
                ]
              },
              {
                "id": "uuid-ia-col-alta-5b",
                "fields": [
                  {
                    "id": "uuid-ia-field-fecha-proximo-control",
                    "key": "ia_fecha_proximo_control",
                    "type": "date",
                    "label": "Próximo Control",
                    "required": false,
                    "ai_help_description": "Fecha sugerida para el próximo control ambulatorio."
                  }
                ]
              }
            ]
          }
        ]
      }
    ],
    "footer": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-ia-section-footer",
          "label": "Pie",
          "display": "default",
          "rows": [
            {
              "id": "uuid-ia-footer-row-1",
              "columns": [
                {
                  "id": "uuid-ia-footer-col-1",
                  "fields": [
                    {
                      "id": "uuid-ia-field-footer-firma",
                      "key": "ia_footer_firma",
                      "type": "fixed_text",
                      "label": "Firma",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Fecha de emisión: {fecha.actual} | Médico: {medico.nombre} — Mat. {medico.matricula}"
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

---

## 3. Consentimiento Informado (CI)

```json
{
  "name": "Consentimiento Informado",
  "description": "Plantilla para la confección del Consentimiento Informado del paciente. Incluye información detallada del procedimiento propuesto, beneficios, riesgos, alternativas y sección de autorización con firmas. Cumple con los requisitos legales de información y comprensión del paciente.",
  "structure": {
    "header": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-ci-section-header",
          "label": "Encabezado",
          "display": "default",
          "rows": [
            {
              "id": "uuid-ci-header-row-1",
              "columns": [
                {
                  "id": "uuid-ci-header-col-1",
                  "fields": [
                    {
                      "id": "uuid-ci-field-header-clinica",
                      "key": "ci_header_clinica",
                      "type": "fixed_text",
                      "label": "Clínica",
                      "required": false,
                      "showLabel": false,
                      "text_content": "{clinica.nombre}",
                      "styling_options": {
                        "bold": true,
                        "size": "lg"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ci-header-row-2",
              "columns": [
                {
                  "id": "uuid-ci-header-col-2",
                  "fields": [
                    {
                      "id": "uuid-ci-field-header-direccion",
                      "key": "ci_header_direccion",
                      "type": "fixed_text",
                      "label": "Dirección",
                      "required": false,
                      "showLabel": false,
                      "text_content": "{clinica.direccion} | Tel: {clinica.telefono}"
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ci-header-row-3",
              "columns": [
                {
                  "id": "uuid-ci-header-col-3",
                  "fields": [
                    {
                      "id": "uuid-ci-field-header-titulo",
                      "key": "ci_header_titulo",
                      "type": "fixed_text",
                      "label": "Título documento",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Consentimiento Informado",
                      "styling_options": {
                        "bold": true,
                        "size": "md"
                      }
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ci-header-row-4",
              "columns": [
                {
                  "id": "uuid-ci-header-col-4",
                  "fields": [
                    {
                      "id": "uuid-ci-field-nro-historia",
                      "key": "ci_nro_historia",
                      "type": "text",
                      "label": "Nro. Historia Clínica",
                      "required": true,
                      "default_value": "{paciente.nro_historia}",
                      "ai_help_description": "Número de historia clínica del paciente. Se completa automáticamente con el número registrado en el sistema."
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    },
    "sections": [
      {
        "id": "uuid-ci-section-informacion",
        "label": "Información del Procedimiento",
        "display": "default",
        "rows": [
          {
            "id": "uuid-ci-row-info-1",
            "columns": [
              {
                "id": "uuid-ci-col-info-1",
                "fields": [
                  {
                    "id": "uuid-ci-field-titulo-procedimiento",
                    "key": "ci_titulo_procedimiento",
                    "type": "fixed_text",
                    "label": "Procedimiento",
                    "required": false,
                    "showLabel": false,
                    "text_content": "Procedimiento: {paciente.nombre}"
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-2",
            "columns": [
              {
                "id": "uuid-ci-col-info-2",
                "fields": [
                  {
                    "id": "uuid-ci-field-procedimiento",
                    "key": "ci_procedimiento",
                    "type": "text",
                    "label": "Procedimiento Propuesto",
                    "required": true,
                    "ai_help_description": "Nombre del procedimiento médico o quirúrgico propuesto."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-3",
            "columns": [
              {
                "id": "uuid-ci-col-info-3",
                "fields": [
                  {
                    "id": "uuid-ci-field-descripcion",
                    "key": "ci_descripcion",
                    "type": "textarea",
                    "label": "Descripción del Procedimiento",
                    "required": true,
                    "ai_help_description": "Descripción detallada del procedimiento en lenguaje claro y comprensible para el paciente, sin tecnicismos innecesarios."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-4",
            "columns": [
              {
                "id": "uuid-ci-col-info-4",
                "fields": [
                  {
                    "id": "uuid-ci-field-tipo-anestesia",
                    "key": "ci_tipo_anestesia",
                    "type": "select",
                    "label": "Tipo de Anestesia",
                    "required": false,
                    "options": [
                      { "label": "Local", "value": "local" },
                      { "label": "Regional", "value": "regional" },
                      { "label": "General", "value": "general" },
                      { "label": "Sedación", "value": "sedacion" }
                    ],
                    "ai_help_description": "Tipo de anestesia prevista para el procedimiento."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-5",
            "columns": [
              {
                "id": "uuid-ci-col-info-5",
                "fields": [
                  {
                    "id": "uuid-ci-field-beneficios",
                    "key": "ci_beneficios",
                    "type": "textarea",
                    "label": "Beneficios Esperados",
                    "required": true,
                    "ai_help_description": "Beneficios esperados del procedimiento: qué se espera lograr y cómo mejorará la condición del paciente."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-6",
            "columns": [
              {
                "id": "uuid-ci-col-info-6a",
                "fields": [
                  {
                    "id": "uuid-ci-field-riesgos-frecuentes",
                    "key": "ci_riesgos_frecuentes",
                    "type": "textarea",
                    "label": "Riesgos Frecuentes",
                    "required": true,
                    "ai_help_description": "Riesgos y complicaciones frecuentes asociadas al procedimiento (incidencia >1%)."
                  }
                ]
              },
              {
                "id": "uuid-ci-col-info-6b",
                "fields": [
                  {
                    "id": "uuid-ci-field-riesgos-graves",
                    "key": "ci_riesgos_graves",
                    "type": "textarea",
                    "label": "Riesgos Graves",
                    "required": false,
                    "ai_help_description": "Riesgos graves o potencialmente mortales, aunque sean poco frecuentes."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-info-7",
            "columns": [
              {
                "id": "uuid-ci-col-info-7",
                "fields": [
                  {
                    "id": "uuid-ci-field-alternativas",
                    "key": "ci_alternativas",
                    "type": "textarea",
                    "label": "Alternativas Disponibles",
                    "required": true,
                    "ai_help_description": "Opciones terapéuticas alternativas al procedimiento propuesto, incluyendo la opción de no realizar el procedimiento y sus consecuencias."
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "uuid-ci-section-autorizacion",
        "label": "Autorización y Firmas",
        "display": "default",
        "rows": [
          {
            "id": "uuid-ci-row-aut-1",
            "columns": [
              {
                "id": "uuid-ci-col-aut-1",
                "fields": [
                  {
                    "id": "uuid-ci-field-declaracion-comprension",
                    "key": "ci_declaracion_comprension",
                    "type": "fixed_text",
                    "label": "Declaración de comprensión",
                    "required": false,
                    "showLabel": false,
                    "text_content": "He comprendido la información proporcionada sobre el procedimiento, sus beneficios, riesgos y alternativas. He tenido la oportunidad de formular preguntas y todas han sido respondidas satisfactoriamente."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-2",
            "columns": [
              {
                "id": "uuid-ci-col-aut-2",
                "fields": [
                  {
                    "id": "uuid-ci-field-preguntas-resueltas",
                    "key": "ci_preguntas_resueltas",
                    "type": "radio",
                    "label": "¿Preguntas respondidas?",
                    "required": true,
                    "options": [
                      { "label": "Sí", "value": "si" },
                      { "label": "No", "value": "no" }
                    ],
                    "ai_help_description": "¿El médico respondió todas sus preguntas sobre el procedimiento?"
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-3",
            "columns": [
              {
                "id": "uuid-ci-col-aut-3",
                "fields": [
                  {
                    "id": "uuid-ci-field-aceptacion",
                    "key": "ci_aceptacion",
                    "type": "checkbox",
                    "label": "Aceptación del Procedimiento",
                    "required": true,
                    "options": [
                      { "label": "Acepto el procedimiento descrito y autorizo su realización", "value": "acepto" }
                    ],
                    "ai_help_description": "Confirmación de que el paciente acepta voluntariamente el procedimiento descrito. Debe marcarse para otorgar el consentimiento."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-4",
            "columns": [
              {
                "id": "uuid-ci-col-aut-4a",
                "fields": [
                  {
                    "id": "uuid-ci-field-nombre-paciente",
                    "key": "ci_nombre_paciente",
                    "type": "text",
                    "label": "Nombre del Paciente",
                    "required": true,
                    "default_value": "{paciente.nombre}",
                    "ai_help_description": "Nombre completo del paciente que otorga el consentimiento."
                  }
                ]
              },
              {
                "id": "uuid-ci-col-aut-4b",
                "fields": [
                  {
                    "id": "uuid-ci-field-firma-paciente",
                    "key": "ci_firma_paciente",
                    "type": "text",
                    "label": "Firma del Paciente",
                    "required": true,
                    "ai_help_description": "Firma del paciente. En formato digital, escribir nombre completo. En papel, firma manuscrita."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-5",
            "columns": [
              {
                "id": "uuid-ci-col-aut-5a",
                "fields": [
                  {
                    "id": "uuid-ci-field-nombre-medico",
                    "key": "ci_nombre_medico",
                    "type": "text",
                    "label": "Nombre del Médico",
                    "required": true,
                    "default_value": "{medico.nombre}",
                    "ai_help_description": "Nombre completo del médico responsable que realiza el procedimiento."
                  }
                ]
              },
              {
                "id": "uuid-ci-col-aut-5b",
                "fields": [
                  {
                    "id": "uuid-ci-field-firma-medico",
                    "key": "ci_firma_medico",
                    "type": "text",
                    "label": "Firma del Médico",
                    "required": false,
                    "ai_help_description": "Firma del médico responsable. En formato digital, escribir nombre completo."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-6",
            "columns": [
              {
                "id": "uuid-ci-col-aut-6a",
                "fields": [
                  {
                    "id": "uuid-ci-field-nombre-testigo",
                    "key": "ci_nombre_testigo",
                    "type": "text",
                    "label": "Nombre del Testigo",
                    "required": false,
                    "ai_help_description": "Nombre completo del testigo presente en la firma del consentimiento."
                  }
                ]
              },
              {
                "id": "uuid-ci-col-aut-6b",
                "fields": [
                  {
                    "id": "uuid-ci-field-firma-testigo",
                    "key": "ci_firma_testigo",
                    "type": "text",
                    "label": "Firma del Testigo",
                    "required": false,
                    "ai_help_description": "Firma del testigo. En formato digital, escribir nombre completo."
                  }
                ]
              }
            ]
          },
          {
            "id": "uuid-ci-row-aut-7",
            "columns": [
              {
                "id": "uuid-ci-col-aut-7",
                "fields": [
                  {
                    "id": "uuid-ci-field-fecha-firma",
                    "key": "ci_fecha_firma",
                    "type": "date",
                    "label": "Fecha de Firma",
                    "required": true,
                    "ai_help_description": "Fecha en que se firma el consentimiento informado. Seleccionar la fecha actual o la fecha acordada para la firma."
                  }
                ]
              }
            ]
          }
        ]
      }
    ],
    "footer": {
      "enabled": true,
      "pageDisplay": "all",
      "sections": [
        {
          "id": "uuid-ci-section-footer",
          "label": "Pie legal",
          "display": "default",
          "rows": [
            {
              "id": "uuid-ci-footer-row-1",
              "columns": [
                {
                  "id": "uuid-ci-footer-col-1",
                  "fields": [
                    {
                      "id": "uuid-ci-field-footer-fecha",
                      "key": "ci_footer_fecha",
                      "type": "fixed_text",
                      "label": "Fecha",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Fecha de firma: {fecha.actual}"
                    }
                  ]
                }
              ]
            },
            {
              "id": "uuid-ci-footer-row-2",
              "columns": [
                {
                  "id": "uuid-ci-footer-col-2",
                  "fields": [
                    {
                      "id": "uuid-ci-field-footer-legal",
                      "key": "ci_footer_legal",
                      "type": "fixed_text",
                      "label": "Nota legal",
                      "required": false,
                      "showLabel": false,
                      "text_content": "Este documento forma parte de la historia clínica del paciente. Se entrega copia al paciente y otra queda archivada en la institución.",
                      "styling_options": {
                        "size": "sm"
                      }
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

---

## Especificaciones técnicas

### Resumen de tipos de campo utilizados

| Tipo | HCG | IA | CI |
|------|-----|----|----|
| `fixed_text` | 5 | 5 | 8 |
| `text` | 2 | 3 | 6 |
| `textarea` | 6 | 5 | 5 |
| `number` | 6 | — | — |
| `multi_select` | 3 | — | — |
| `select` | — | 2 | 1 |
| `radio` | — | — | 1 |
| `checkbox` | — | — | 1 |
| `date` | — | 3 | — |
| `dynamic_table` | 1 | 1 | — |
| `horizontal_separator` | 1 | — | — |
| **Total campos** | **24** | **19** | **22** |

### Variables del sistema utilizadas por plantilla

| Plantilla | Variables |
|-----------|-----------|
| HCG | `{clinica.nombre}`, `{paciente.nombre}`, `{paciente.nro_historia}`, `{paciente.edad}`, `{paciente.sexo}`, `{fecha.formato_largo}`, `{medico.nombre}`, `{medico.matricula}`, `{medico.especialidad}`, `{fecha.actual}` |
| IA | `{clinica.nombre}`, `{clinica.direccion}`, `{clinica.telefono}`, `{paciente.nombre}`, `{paciente.nro_historia}`, `{fecha.actual}`, `{medico.nombre}`, `{medico.matricula}` |
| CI | `{clinica.nombre}`, `{clinica.direccion}`, `{clinica.telefono}`, `{paciente.nombre}`, `{fecha.formato_largo}`, `{fecha.actual}`, `{medico.nombre}` |

### Recomendaciones para el seeder de Laravel

```php
// Ejemplo: DatabaseSeeder.php
$templates = [
    'Historia Clínica General'   => $hcgJson,
    'Informe de Alta'            => $iaJson,
    'Consentimiento Informado'   => $ciJson,
];

foreach ($templates as $name => $json) {
    $structure = json_decode($json, true);
    // Reemplazar UUIDs placeholders con UUIDs reales
    $structure = $this->replaceUuids($structure);

    DB::table('report_templates')->insert([
        'name'        => $name,
        'description' => $structure['description'],
        'structure'   => json_encode($structure['structure']),
        'is_active'   => true,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

/**
 * Reemplaza recursivamente todos los UUIDs placeholders en la estructura.
 */
private function replaceUuids(array $data): array
{
    foreach ($data as $key => &$value) {
        if (in_array($key, ['id']) && is_string($value) && str_starts_with($value, 'uuid-')) {
            $value = Str::uuid()->toString();
        } elseif (is_array($value)) {
            $value = $this->replaceUuids($value);
        }
    }
    return $data;
}
```
