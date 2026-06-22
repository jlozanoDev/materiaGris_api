<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    public function definition(): array
    {
        $fakerEs = \Faker\Factory::create('es_ES');

        return [
            'name' => $fakerEs->sentence(3),
            'description' => $fakerEs->paragraph(),
            'is_active' => $fakerEs->boolean(90),
            'structure' => [
                'sections' => [
                    [
                        'title' => 'Sección principal',
                        'rows' => [
                            [
                                'columns' => [
                                    [
                                        'type' => 'text',
                                        'label' => 'Observaciones',
                                        'field' => 'observaciones',
                                        'required' => false,
                                        'ai_help_description' => 'Describe los hallazgos clínicos observados durante la consulta',
                                    ],
                                    [
                                        'type' => 'text',
                                        'label' => 'Diagnóstico',
                                        'field' => 'diagnostico',
                                        'required' => true,
                                        'ai_help_description' => 'Indica el diagnóstico principal basado en los síntomas reportados',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
