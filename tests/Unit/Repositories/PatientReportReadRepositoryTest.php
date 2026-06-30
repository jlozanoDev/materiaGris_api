<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\Report\PatientReportReadRepository;
use App\Models\PatientReport;
use App\Models\Patient;
use App\Models\User;
use App\Models\ReportTemplate;

class PatientReportReadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_filters_by_patient_name(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez']);
        $user = User::factory()->create();
        $template = ReportTemplate::factory()->create();

        PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        $repo = new PatientReportReadRepository();
        $result = $repo->listar(['patient' => 'Juan']);

        $this->assertCount(1, $result);
    }

    public function test_listar_filters_by_date_range(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->create();
        $template = ReportTemplate::factory()->create();

        PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        $repo = new PatientReportReadRepository();
        $result = $repo->listar([
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]);

        $this->assertCount(1, $result);
    }

    public function test_listar_filters_by_template_id(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->create();
        $template = ReportTemplate::factory()->create();

        PatientReport::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        $repo = new PatientReportReadRepository();
        $result = $repo->listar(['template_id' => $template->id]);

        $this->assertCount(1, $result);
    }
}
