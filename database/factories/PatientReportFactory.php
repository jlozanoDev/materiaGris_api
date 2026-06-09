<?php

namespace Database\Factories;

use App\Models\PatientReport;
use App\Models\Patient;
use App\Models\User;
use App\Models\ReportTemplate;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PatientReportFactory extends Factory
{
    protected $model = PatientReport::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'template_id' => ReportTemplate::factory(),
            'status' => ReportStatus::Draft,
            'template_structure_snapshot' => [
                'sections' => [
                    [
                        'title' => 'Datos del informe',
                        'rows' => [
                            [
                                'columns' => [
                                    [
                                        'type' => 'text',
                                        'label' => 'Diagnóstico',
                                        'field' => 'diagnostico',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'values' => [],
            'signature_path' => null,
            'pdf_path' => null,
            'signed_at' => null,
            'closed_at' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Signed,
            'signed_at' => Carbon::now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Closed,
            'signed_at' => Carbon::now(),
            'closed_at' => Carbon::now(),
            'pdf_path' => 'reports/report-' . fake()->uuid() . '.pdf',
        ]);
    }
}
