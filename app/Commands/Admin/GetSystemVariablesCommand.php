<?php

namespace App\Commands\Admin;

class GetSystemVariablesCommand
{
    /**
     * Devuelve el catálogo completo de variables del sistema disponibles
     * para interpolación en campos fixed_text del builder de plantillas.
     *
     * Sintaxis de interpolación: {categoria.clave}
     */
    public function execute(): array
    {
        return [
            // ── paciente ────────────────────────────────────────────────
            ['category' => 'paciente', 'key' => 'nombre',          'label' => 'Nombre del paciente',       'description' => 'Nombre completo del paciente'],
            ['category' => 'paciente', 'key' => 'apellido',        'label' => 'Apellido del paciente',     'description' => 'Apellido del paciente'],
            ['category' => 'paciente', 'key' => 'nombre_completo', 'label' => 'Nombre completo',           'description' => 'Nombre y apellido del paciente'],
            ['category' => 'paciente', 'key' => 'edad',            'label' => 'Edad',                       'description' => 'Edad del paciente en años'],
            ['category' => 'paciente', 'key' => 'sexo',            'label' => 'Sexo',                       'description' => 'Sexo del paciente'],
            ['category' => 'paciente', 'key' => 'nro_historia',    'label' => 'Nro. Historia Clínica',      'description' => 'Número de historia clínica'],
            ['category' => 'paciente', 'key' => 'dni',             'label' => 'DNI',                        'description' => 'Documento Nacional de Identidad'],
            ['category' => 'paciente', 'key' => 'obra_social',     'label' => 'Obra social',                'description' => 'Obra social o cobertura médica'],
            ['category' => 'paciente', 'key' => 'fecha_nacimiento','label' => 'Fecha de nacimiento',        'description' => 'Fecha de nacimiento del paciente'],
            ['category' => 'paciente', 'key' => 'direccion',       'label' => 'Dirección',                  'description' => 'Dirección del paciente'],
            ['category' => 'paciente', 'key' => 'telefono',        'label' => 'Teléfono',                   'description' => 'Teléfono de contacto del paciente'],
            ['category' => 'paciente', 'key' => 'email',           'label' => 'Email',                      'description' => 'Correo electrónico del paciente'],
            ['category' => 'paciente', 'key' => 'peso',            'label' => 'Peso',                       'description' => 'Peso del paciente en kg'],
            ['category' => 'paciente', 'key' => 'altura',          'label' => 'Altura',                     'description' => 'Altura del paciente en cm'],
            ['category' => 'paciente', 'key' => 'grupo_sanguineo', 'label' => 'Grupo sanguíneo',            'description' => 'Grupo sanguíneo y factor RH'],

            // ── clinica ────────────────────────────────────────────────
            ['category' => 'clinica',  'key' => 'nombre',          'label' => 'Nombre de la clínica',       'description' => 'Nombre de la clínica o institución'],
            ['category' => 'clinica',  'key' => 'direccion',       'label' => 'Dirección',                  'description' => 'Dirección de la clínica'],
            ['category' => 'clinica',  'key' => 'telefono',        'label' => 'Teléfono',                   'description' => 'Teléfono de contacto'],
            ['category' => 'clinica',  'key' => 'email',           'label' => 'Email',                      'description' => 'Correo electrónico de la clínica'],
            ['category' => 'clinica',  'key' => 'ciudad',          'label' => 'Ciudad',                     'description' => 'Ciudad donde se ubica la clínica'],
            ['category' => 'clinica',  'key' => 'provincia',       'label' => 'Provincia',                  'description' => 'Provincia donde se ubica la clínica'],
            ['category' => 'clinica',  'key' => 'codigo_postal',   'label' => 'Código Postal',              'description' => 'Código postal de la clínica'],
            ['category' => 'clinica',  'key' => 'web',             'label' => 'Sitio web',                  'description' => 'URL del sitio web de la clínica'],
            ['category' => 'clinica',  'key' => 'logo',            'label' => 'Logo',                       'description' => 'Logo o imagen institucional'],

            // ── fecha ──────────────────────────────────────────────────
            ['category' => 'fecha',    'key' => 'actual',          'label' => 'Fecha actual',               'description' => 'Fecha del día de hoy (formato dd/mm/aaaa)'],
            ['category' => 'fecha',    'key' => 'formato_largo',   'label' => 'Fecha formato largo',        'description' => 'Fecha en formato largo (ej: 14 de junio de 2026)'],
            ['category' => 'fecha',    'key' => 'corta',           'label' => 'Fecha corta',                'description' => 'Fecha en formato corto (dd/mm/aa)'],
            ['category' => 'fecha',    'key' => 'hora',            'label' => 'Hora actual',                'description' => 'Hora actual (hh:mm)'],
            ['category' => 'fecha',    'key' => 'fecha_hora',      'label' => 'Fecha y hora',               'description' => 'Fecha y hora actual (dd/mm/aaaa hh:mm)'],
            ['category' => 'fecha',    'key' => 'anio',            'label' => 'Año actual',                 'description' => 'Año en curso (aaaa)'],
            ['category' => 'fecha',    'key' => 'mes',             'label' => 'Mes actual',                 'description' => 'Mes en curso en texto (ej: junio)'],

            // ── usuario ────────────────────────────────────────────────
            ['category' => 'usuario',  'key' => 'nombre',          'label' => 'Nombre del usuario',         'description' => 'Nombre del profesional que genera el informe'],
            ['category' => 'usuario',  'key' => 'apellido',        'label' => 'Apellido del usuario',       'description' => 'Apellido del profesional que genera el informe'],
            ['category' => 'usuario',  'key' => 'nombre_completo', 'label' => 'Nombre completo',           'description' => 'Nombre y apellido del usuario'],
            ['category' => 'usuario',  'key' => 'matricula',       'label' => 'Matrícula',                  'description' => 'Número de matrícula profesional'],
            ['category' => 'usuario',  'key' => 'email',           'label' => 'Email',                      'description' => 'Correo electrónico del profesional'],
            ['category' => 'usuario',  'key' => 'especialidad',    'label' => 'Especialidad',               'description' => 'Especialidad del profesional'],
            ['category' => 'usuario',  'key' => 'rol',             'label' => 'Rol',                        'description' => 'Rol o cargo en la institución'],
            ['category' => 'usuario',  'key' => 'telefono',        'label' => 'Teléfono',                   'description' => 'Teléfono de contacto del profesional'],

            // ── medico ─────────────────────────────────────────────────
            ['category' => 'medico',   'key' => 'nombre',          'label' => 'Nombre del médico',          'description' => 'Nombre completo del médico tratante'],
            ['category' => 'medico',   'key' => 'apellido',        'label' => 'Apellido del médico',        'description' => 'Apellido del médico tratante'],
            ['category' => 'medico',   'key' => 'nombre_completo', 'label' => 'Nombre completo',           'description' => 'Nombre y apellido del médico'],
            ['category' => 'medico',   'key' => 'matricula',       'label' => 'Matrícula',                  'description' => 'Número de matrícula del médico'],
            ['category' => 'medico',   'key' => 'especialidad',    'label' => 'Especialidad',               'description' => 'Especialidad del médico'],
            ['category' => 'medico',   'key' => 'email',           'label' => 'Email',                      'description' => 'Correo electrónico del médico'],
            ['category' => 'medico',   'key' => 'telefono',        'label' => 'Teléfono',                   'description' => 'Teléfono de contacto del médico'],
            ['category' => 'medico',   'key' => 'dias_consulta',   'label' => 'Días de consulta',           'description' => 'Días de atención del médico'],
            ['category' => 'medico',   'key' => 'horario',         'label' => 'Horario',                    'description' => 'Horario de atención del médico'],
            ['category' => 'medico',   'key' => 'nro_colegiado',   'label' => 'Nro. de colegiado',          'description' => 'Número de colegiación profesional'],

            // ── informe ────────────────────────────────────────────────
            ['category' => 'informe',  'key' => 'titulo',          'label' => 'Título del informe',         'description' => 'Título o nombre del informe'],
            ['category' => 'informe',  'key' => 'tipo',            'label' => 'Tipo de informe',            'description' => 'Tipo de informe según la plantilla'],
            ['category' => 'informe',  'key' => 'fecha_creacion',  'label' => 'Fecha de creación',          'description' => 'Fecha en que se inició el informe'],
            ['category' => 'informe',  'key' => 'fecha_firma',     'label' => 'Fecha de firma',             'description' => 'Fecha en que se firmó el informe'],
            ['category' => 'informe',  'key' => 'pagina_actual',          'label' => 'Página actual',               'description' => 'Número de la página actual'],
            ['category' => 'informe',  'key' => 'pagina_total',           'label' => 'Total de páginas',            'description' => 'Cantidad total de páginas del informe'],
            ['category' => 'informe',  'key' => 'pagina_actual_de_total', 'label' => 'Página X de Y',              'description' => 'Página actual sobre total (ej: Página 3 de 12)'],
        ];
    }

    /**
     * Devuelve las variables agrupadas por categoría (útil para el frontend).
     */
    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->execute() as $variable) {
            $grouped[$variable['category']][] = $variable;
        }
        return $grouped;
    }
}
