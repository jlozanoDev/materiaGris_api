<?php

namespace App\Commands\Admin\SystemVariable;

use App\DTOs\SystemVariable;

class GetSystemVariablesCommand
{
    /**
     * Devuelve el catálogo completo de variables del sistema disponibles
     * para interpolación en campos fixed_text del builder de plantillas.
     *
     * Sintaxis de interpolación: {categoria.clave}
     *
     * @return array<int, SystemVariable>
     */
    public function execute(): array
    {
        return [
            // ── paciente ────────────────────────────────────────────────
            new SystemVariable('paciente', 'nombre',          'Nombre del paciente',       'Nombre completo del paciente'),
            new SystemVariable('paciente', 'apellido',        'Apellido del paciente',     'Apellido del paciente'),
            new SystemVariable('paciente', 'nombre_completo', 'Nombre completo',           'Nombre y apellido del paciente'),
            new SystemVariable('paciente', 'edad',            'Edad',                       'Edad del paciente en años'),
            new SystemVariable('paciente', 'sexo',            'Sexo',                       'Sexo del paciente'),
            new SystemVariable('paciente', 'nro_historia',    'Nro. Historia Clínica',      'Número de historia clínica'),
            new SystemVariable('paciente', 'dni',             'DNI',                        'Documento Nacional de Identidad'),
            new SystemVariable('paciente', 'obra_social',     'Obra social',                'Obra social o cobertura médica'),
            new SystemVariable('paciente', 'fecha_nacimiento','Fecha de nacimiento',        'Fecha de nacimiento del paciente'),
            new SystemVariable('paciente', 'direccion',       'Dirección',                  'Dirección del paciente'),
            new SystemVariable('paciente', 'telefono',        'Teléfono',                   'Teléfono de contacto del paciente'),
            new SystemVariable('paciente', 'email',           'Email',                      'Correo electrónico del paciente'),
            new SystemVariable('paciente', 'peso',            'Peso',                       'Peso del paciente en kg'),
            new SystemVariable('paciente', 'altura',          'Altura',                     'Altura del paciente en cm'),
            new SystemVariable('paciente', 'grupo_sanguineo', 'Grupo sanguíneo',            'Grupo sanguíneo y factor RH'),

            // ── clinica ────────────────────────────────────────────────
            new SystemVariable('clinica',  'nombre',          'Nombre de la clínica',       'Nombre de la clínica o institución'),
            new SystemVariable('clinica',  'direccion',       'Dirección',                  'Dirección de la clínica'),
            new SystemVariable('clinica',  'telefono',        'Teléfono',                   'Teléfono de contacto'),
            new SystemVariable('clinica',  'email',           'Email',                      'Correo electrónico de la clínica'),
            new SystemVariable('clinica',  'ciudad',          'Ciudad',                     'Ciudad donde se ubica la clínica'),
            new SystemVariable('clinica',  'provincia',       'Provincia',                  'Provincia donde se ubica la clínica'),
            new SystemVariable('clinica',  'codigo_postal',   'Código Postal',              'Código postal de la clínica'),
            new SystemVariable('clinica',  'web',             'Sitio web',                  'URL del sitio web de la clínica'),
            new SystemVariable('clinica',  'logo',            'Logo',                       'Logo o imagen institucional'),

            // ── fecha ──────────────────────────────────────────────────
            new SystemVariable('fecha',    'actual',          'Fecha actual',               'Fecha del día de hoy (formato dd/mm/aaaa)'),
            new SystemVariable('fecha',    'formato_largo',   'Fecha formato largo',        'Fecha en formato largo (ej: 14 de junio de 2026)'),
            new SystemVariable('fecha',    'corta',           'Fecha corta',                'Fecha en formato corto (dd/mm/aa)'),
            new SystemVariable('fecha',    'hora',            'Hora actual',                'Hora actual (hh:mm)'),
            new SystemVariable('fecha',    'fecha_hora',      'Fecha y hora',               'Fecha y hora actual (dd/mm/aaaa hh:mm)'),
            new SystemVariable('fecha',    'anio',            'Año actual',                 'Año en curso (aaaa)'),
            new SystemVariable('fecha',    'mes',             'Mes actual',                 'Mes en curso en texto (ej: junio)'),

            // ── usuario ────────────────────────────────────────────────
            new SystemVariable('usuario',  'nombre',          'Nombre del usuario',         'Nombre del profesional que genera el informe'),
            new SystemVariable('usuario',  'apellido',        'Apellido del usuario',       'Apellido del profesional que genera el informe'),
            new SystemVariable('usuario',  'nombre_completo', 'Nombre completo',           'Nombre y apellido del usuario'),
            new SystemVariable('usuario',  'matricula',       'Matrícula',                  'Número de matrícula profesional'),
            new SystemVariable('usuario',  'email',           'Email',                      'Correo electrónico del profesional'),
            new SystemVariable('usuario',  'especialidad',    'Especialidad',               'Especialidad del profesional'),
            new SystemVariable('usuario',  'rol',             'Rol',                        'Rol o cargo en la institución'),
            new SystemVariable('usuario',  'telefono',        'Teléfono',                   'Teléfono de contacto del profesional'),

            // ── medico ─────────────────────────────────────────────────
            new SystemVariable('medico',   'nombre',          'Nombre del médico',          'Nombre completo del médico tratante'),
            new SystemVariable('medico',   'apellido',        'Apellido del médico',        'Apellido del médico tratante'),
            new SystemVariable('medico',   'nombre_completo', 'Nombre completo',           'Nombre y apellido del médico'),
            new SystemVariable('medico',   'matricula',       'Matrícula',                  'Número de matrícula del médico'),
            new SystemVariable('medico',   'especialidad',    'Especialidad',               'Especialidad del médico'),
            new SystemVariable('medico',   'email',           'Email',                      'Correo electrónico del médico'),
            new SystemVariable('medico',   'telefono',        'Teléfono',                   'Teléfono de contacto del médico'),
            new SystemVariable('medico',   'dias_consulta',   'Días de consulta',           'Días de atención del médico'),
            new SystemVariable('medico',   'horario',         'Horario',                    'Horario de atención del médico'),
            new SystemVariable('medico',   'nro_colegiado',   'Nro. de colegiado',          'Número de colegiación profesional'),

            // ── informe ────────────────────────────────────────────────
            new SystemVariable('informe',  'titulo',          'Título del informe',         'Título o nombre del informe'),
            new SystemVariable('informe',  'tipo',            'Tipo de informe',            'Tipo de informe según la plantilla'),
            new SystemVariable('informe',  'fecha_creacion',  'Fecha de creación',          'Fecha en que se inició el informe'),
            new SystemVariable('informe',  'fecha_firma',     'Fecha de firma',             'Fecha en que se firmó el informe'),
            new SystemVariable('informe',  'pagina_actual',          'Página actual',               'Número de la página actual'),
            new SystemVariable('informe',  'pagina_total',           'Total de páginas',            'Cantidad total de páginas del informe'),
            new SystemVariable('informe',  'pagina_actual_de_total', 'Página X de Y',              'Página actual sobre total (ej: Página 3 de 12)'),
        ];
    }

    /**
     * Devuelve las variables agrupadas por categoría (útil para el frontend).
     *
     * @return array<string, array<int, SystemVariable>>
     */
    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->execute() as $variable) {
            $grouped[$variable->category][] = $variable;
        }
        return $grouped;
    }
}
